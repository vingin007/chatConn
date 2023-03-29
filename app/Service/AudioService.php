<?php

namespace App\Service;


use App\Event\AudioMessageSend;
use App\Exception\BusinessException;
use App\Model\Chat;
use App\Model\Message;
use App\Model\User;
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
        $fileSystem = ApplicationContext::getContainer()->get(FilesystemFactory::class)->get('local');
        $tempPath = '/temp';
        $filePath = $tempPath . '/' . uniqid() . '.' . $file->getExtension();
        $fileRealPath = BASE_PATH.'/runtime/storage'.$filePath;
        try {
            $stream = fopen($file->getRealPath(), 'r+');
            $fileSystem->writeStream($filePath, $stream);
            fclose($stream);
        } catch (FilesystemException $e) {
            throw $e;
        }
        $filename = $user->id . '_' . uniqid() . '.wav';
        $flacFilePath = BASE_PATH.'/runtime/storage'.$tempPath . '/' . $filename;
        try {
            $ffmpeg = FFMpeg::create();
            $audio = $ffmpeg->open($fileRealPath);
            $audio->save(new Wav(), $flacFilePath);

            // 将转换后的FLAC文件上传到Google Cloud Storage
            $this->gcsService->upload($filename, fopen($flacFilePath, 'r'));
            $store_url = $this->gcsService->get($filename);
            $message = $this->openaiService->convertAudioToText($flacFilePath);
            //写聊天记录
            $message = $this->chatRecordService->addChatLog($user, $chat, $message, 'audio', $filename, $store_url, true);
            if ($fileSystem->has($filePath)) {
                $fileSystem->delete($filePath);
            }
            if ($fileSystem->has($flacFilePath)) {
                $fileSystem->delete($flacFilePath);
            }
        }catch (RuntimeException|InvalidArgumentException $e){
            if ($fileSystem->has($filePath)) {
                $fileSystem->delete($filePath);
            }
            Db::rollBack();
            throw $e;
        }catch (BusinessException $e){
            if ($fileSystem->has($filePath)) {
                $fileSystem->delete($filePath);
            }
            if ($fileSystem->has($flacFilePath)) {
                $fileSystem->delete($flacFilePath);
            }
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
            $store_name = 'answer_' . $user->id. '_' . uniqid() . '.wav';
            //转回复语音
            $result = $this->ttsService->convertTextToAudio($stream_content, $store_name);
            //写数据库,如果写数据库失败就
            $message = $this->chatRecordService->addChatLog($user, $chat, $stream_content, 'audio', $store_name, $result['url'], false);
            $this->eventDispatcher->dispatch(new AudioMessageSend($user));
        }catch (ApiException|ValidationException|GuzzleException|BusinessException $e){
            Db::rollBack();
            throw $e;
        }
        Db::commit();
        return $message;
    }
}
