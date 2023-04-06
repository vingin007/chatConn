<?php

namespace App\Service;


use App\Event\AudioMessageSend;
use App\Exception\BusinessException;
use App\Model\Chat;
use App\Model\Message;
use App\Model\User;
use Aws\S3\Exception\S3Exception;
use Exception;
use FFMpeg\Exception\InvalidArgumentException;
use FFMpeg\Exception\RuntimeException;
use FFMpeg\FFMpeg;
use FFMpeg\Format\Audio\Wav;
use Google\ApiCore\ApiException;
use Google\ApiCore\ValidationException;
use GuzzleHttp\Exception\GuzzleException;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Filesystem\FilesystemFactory;
use Hyperf\Utils\ApplicationContext;
use League\Flysystem\FilesystemException;
use Psr\EventDispatcher\EventDispatcherInterface;

class AudioService
{
    #[Inject]
    protected GcsService $gcsService;
    #[Inject]
    protected SttService $sttService;
    #[Inject]
    protected ChatRecordService $chatRecordService;
    #[Inject]
    protected TtsService $ttsService;
    #[Inject]
    protected OpenaiService $openaiService;
    #[Inject]
    protected EventDispatcherInterface $eventDispatcher;
    #[Inject]
    protected S3Service $s3Service;
    #[Inject]
    protected PollyService $pollyService;
    protected FilterWordService $filterWordService;

    public function upload(User $user,Chat $chat,$file)
    {
        if (empty($file)) {
            throw new BusinessException(401,'上传文件不能为空');
        }
        // 确认上传的文件是WebM或MP3格式的音频文件
        if ($file->getClientMediaType() !== 'audio/webm' && $file->getClientMediaType() !== 'audio/mpeg' && $file->getClientMediaType() !== 'audio/x-aac') {
            throw new BusinessException(400,'Unsupported audio format');
        }
        $filesystem = ApplicationContext::getContainer()->get(FilesystemFactory::class)->get('local');
        $filename = $user->id . '_' . uniqid() . '.mp3';
        try {
            $stream = fopen($file->getRealPath(),'r');
            $filePath = '/runtime/storage/'.$filename;
            $filesystem->writeStream($filename, $stream);
            $this->s3Service->uploadFile($filename, $stream);
            fclose($stream);
            $message = $this->openaiService->convertAudioToText(BASE_PATH.$filePath);
            //写聊天记录
            $message = $this->chatRecordService->addChatLog($user, $chat, $message, 'audio', $filename, '', true);
            if($filesystem->has($filename)){
                $filesystem->delete($filename);
            }
        }catch (RuntimeException|InvalidArgumentException|FilesystemException $e){
            if($filesystem->has($filename)){
                $filesystem->delete($filename);
            }
            Db::rollBack();
            throw $e;
        }
        return $message;
    }
    public function send(User $user,Chat $chat,Message $message)
    {
        Db::beginTransaction();
        try {
            //发起gpt请求
            $stream_content = $this->openaiService->audio(['role' => 'user', 'content' => $message->content], $user, $chat);
            if (empty($stream_content)) {
                throw new BusinessException('ai回复结果为空');
            }
            $stream = $this->pollyService->textToSpeech($stream_content);
            $store_name = 'answer_'.$user->id . '_' . uniqid() . '.mp3';
            //上传到gcs
            $result = $this->s3Service->uploadFile($store_name, $stream);
            //写数据库,如果写数据库失败就
            $message = $this->chatRecordService->addChatLog($user, $chat, $stream_content, 'audio', $store_name, '', false);
            $this->eventDispatcher->dispatch(new AudioMessageSend($user));
        }catch (ApiException|S3Exception|GuzzleException|BusinessException $e){
            Db::rollBack();
            throw $e;
        }
        Db::commit();
        return $message;
    }
}
