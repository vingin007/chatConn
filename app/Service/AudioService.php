<?php

namespace App\Service;


use App\Event\AudioMessageSend;
use App\Exception\BusinessException;
use App\Model\Chat;
use App\Model\Message;
use App\Model\TransOrder;
use App\Model\User;
use App\Model\Video;
use Aws\S3\Exception\S3Exception;
use Exception;
use FFMpeg\Exception\InvalidArgumentException;
use FFMpeg\Exception\RuntimeException;
use FFMpeg\FFMpeg;
use FFMpeg\Format\Video\X264;
use Google\ApiCore\ApiException;
use GuzzleHttp\Exception\GuzzleException;
use Hyperf\AsyncQueue\Annotation\AsyncQueueMessage;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Filesystem\FilesystemFactory;
use Hyperf\Logger\Logger;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Utils\ApplicationContext;
use League\Flysystem\FilesystemException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;


class AudioService
{
    #[Inject]
    protected ChatRecordService $chatRecordService;
    #[Inject]
    protected OpenaiService $openaiService;
    #[Inject]
    protected EventDispatcherInterface $eventDispatcher;
    #[Inject]
    protected S3Service $s3Service;
    #[Inject]
    protected PollyService $pollyService;
    protected LoggerInterface $logger;
    public function __construct(LoggerFactory $loggerFactory)
    {
        // 第一个参数对应日志的 name, 第二个参数对应 config/autoload/logger.php 内的 key
        $this->logger = $loggerFactory->get('log', 'default');
    }
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

    public function justUpload($user,$file)
    {
        if (empty($file)) {
            throw new BusinessException(401,'上传文件不能为空');
        }
        $mime_type = $file->getMimeType();
        if (!str_starts_with($mime_type, 'video/')) {
            throw new BusinessException(400,'文件必须是视频文件');
        }
        $hash = hash_file('sha256', $file->getRealPath());
        $record = Video::query()->where('hash', $hash)->where('user_id',$user->id)->first();
        if ($record) {
            return $record;
        }
        // 获取文件大小（以字节为单位）
        $fileSize = $file->getSize();

        // 将文件大小转换为兆字节（MB）
        $fileSizeInMB = $fileSize / (1024 * 1024);

        // 判断文件大小是否超过25MB
        if ($fileSizeInMB > 25) {
            throw new BusinessException(400,'文件大小超过25MB，请上传较小的文件。');
        }
        $ffmpeg = FFMpeg::create();

        // 打开视频文件
        $video = $ffmpeg->open($file->getRealPath());

        // 获取视频时长
        $durationInSeconds = $video->getFFProbe()->format($file->getRealPath())->get('duration');
        $filename = 'pre_'.uniqid() .'.'. $file->getExtension();
        $stream = fopen($file->getRealPath(),'r');
        try {
            $filesystem = ApplicationContext::getContainer()->get(FilesystemFactory::class)->get('local');
            $filesystem->writeStream($filename, $stream);
            fclose($stream);
            $video = new Video;
            $video->user_id = $user->id;
            $video->store_name = $filename;
            $video->duration = $durationInSeconds;
            $video->size = $fileSize;
            $video->format = $file->getExtension();
            $video->hash = $hash;
            $video->status = 0;
            if ($user->mobile == '13608303371') {
                $video->status = 1;
            }
            $video->save();
        }catch (FilesystemException|NotFoundExceptionInterface|ContainerExceptionInterface $e){
            throw $e;
        }
        return $video;
    }
    #[AsyncQueueMessage(delay: 86400, maxAttempts: 1)]
    public function deleteFileAfterTime($video_id)
    {
        $video = Video::first($video_id);
        if ($video) {
            $filesystem = ApplicationContext::getContainer()->get(FilesystemFactory::class)->get('local');
            $exists = $filesystem->has($video->store_name);
            if ($exists) {
                $filesystem->delete($video->store_name);
            }
            $video->delete();
        }
        return true;
    }
    #[AsyncQueueMessage]
    public function uploadAndText($store_name,$user_id,$lang = 'chinese',$is_trans = true)
    {
        $filesystem = ApplicationContext::getContainer()->get(FilesystemFactory::class)->get('local');
        $exists = $filesystem->has($store_name);
        if(!$exists){
            throw new BusinessException(400,'文件不存在');
        }
        $videoPath = BASE_PATH.'/runtime/storage/'.$store_name;
        // 使用 FFmpeg 从视频中提取音频
        $ffmpeg = FFMpeg::create();
        // 打开视频文件
        $video = $ffmpeg->open($videoPath);
        $filename = uniqid() . '.wav';
        $audioname = uniqid() . '_rate.wav';
        $filePath = '/runtime/storage/';
        // 设置音频输出格式和文件名
        $audioFormat = new \FFMpeg\Format\Audio\Wav();
        $audioFormat->setAudioChannels(1); // 设置为单声道
        $audioFormat->setAudioKiloBitrate(16); // 设置音频比特率（kbps）
        $outputAudioPath = BASE_PATH.$filePath.$filename;
        $audioPath = BASE_PATH.$filePath.$audioname;
        // 从视频中提取音频
        $video->save($audioFormat, $outputAudioPath);
        $audio = $ffmpeg->open($outputAudioPath);
        $audio->filters()->resample(16000);
        $audio->save($audioFormat, $audioPath);
        // 用openai的语音转文字接口转换音频为文字
        $message = $this->openaiService->convertAudioToText($audioPath,true);
        $text = $message->text;
        //用aws的转录接口获取语音段和文本
        $voice_segments = $message['segments'];

        $subtitle_data = [];
        $_text = '';
        $count = count($voice_segments);
        foreach ($voice_segments as $index => $item) {
            $start_time = $item['start'];
            $end_time = $item['end'];
            $_text .= '|||'.$item['text'];
            $text = $this->wrapText($item['text'],40);
            $subtitle_data[] = [
                'start_time' => $start_time,
                'end_time' => $end_time,
                'text' => $text
            ];
        }
        $this->logger->info('翻译结果:'.$_text);
        //向gpt请求翻译
        $request_message = [
            [
                'role' => 'system',
                'content' => 'You are a translator who can translate any language.'
            ],
            [
                'role' => 'user',
                'content' => 'Keep the "|||" in the text.Translate the following text to '.$lang.':'.$_text
            ]
        ];
        $chinese_text = $this->openaiService->chat($request_message);
        $this->logger->info('翻译结果:'.$chinese_text);
        $chinese_arr = explode('|||',$chinese_text);
        $this->logger->info('翻译结果数组:'.json_encode($chinese_arr));
        //生成字幕文件
        $srt_content = "";
        $srt_chinese = "";
        foreach ($subtitle_data as $index => $item) {
            $start_time = secondsToTimecode($item['start_time']);
            $end_time = secondsToTimecode($item['end_time']);
            $text = $item['text'];
            $srt_content .= ($index + 1) . PHP_EOL;
            $srt_content .= $start_time . " --> " . $end_time . PHP_EOL;
            $srt_content .= $text . PHP_EOL . PHP_EOL;
            $srt_chinese .= ($index + 1) . PHP_EOL;
            $srt_chinese .= $start_time . " --> " . $end_time . PHP_EOL;
            $ch = $this->wrapChinese($chinese_arr[$index+1],15);
            $srt_chinese .= $ch . PHP_EOL . PHP_EOL;
        }
        $srt = BASE_PATH.'/storage/srt/'.$store_name.'.srt';
        $srt_ch = BASE_PATH.'/storage/srt/'.$store_name.'_ch.srt';
        file_put_contents($srt, $srt_content);
        file_put_contents($srt_ch, $srt_chinese);
        $video = $ffmpeg->open($videoPath);
        //fanyihou
        $assPath2 = BASE_PATH.'/storage/srt/'.$store_name.'_ch.ass';
        $command = "ffmpeg -i {$srt_ch} {$assPath2}";
        $output = null;
        $returnVar = null;

        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            throw new RuntimeException('SRT to ASS conversion failed for translated subtitles');
        }
        $translatedAssContent = file_get_contents($assPath2);
        $assMergedContent = $translatedAssContent;
        if($is_trans === true){
            //yuan
            $assPath = BASE_PATH.'/storage/srt/'.$store_name.'.ass';
            $command = "ffmpeg -i {$srt} {$assPath}";
            $output = null;
            $returnVar = null;

            exec($command, $output, $returnVar);

            if ($returnVar !== 0) {
                throw new RuntimeException('SRT to ASS conversion failed');
            }
            $originalAssContent = file_get_contents($assPath);
            // 将翻译字幕的样式名称更改为 'Translated'
            $translatedAssContent = preg_replace('/^Style: Default,(.*)$/m', 'Style: Translated,$1', $translatedAssContent);
            $translatedAssContent = preg_replace('/\[Script Info\](.*?)\[V4\+ Styles\](.*?)\n/mis', '', $translatedAssContent);
            $assMergedContent = $originalAssContent .PHP_EOL. $translatedAssContent;
        }

        $assMergedContent = preg_replace_callback(
            '/^Style: (Default|Translated),(.*)$/m',
            function ($matches) {
                list($styleType, $styleParams) = array($matches[1], $matches[2]);

                list($fontName, $fontSize, $primaryColor, $secondaryColor, $outlineColor, $backColor, $bold, $italic, $underline, $strikeOut, $scaleX, $scaleY, $spacing, $angle, $borderStyle, $outline, $shadow, $alignment, $marginL, $marginR, $marginV, $encoding) = array_map('trim', explode(',', $styleParams));

                $fontName = 'Arial';
                $fontSize = 14;
                $alignment = 2; // 屏幕底部中间位置

                // 根据字幕类型调整垂直边距
                $marginV = 30;
                return 'Style: Default,' . implode(',', [$fontName, $fontSize, $primaryColor, $secondaryColor, $outlineColor, $backColor, $bold, $italic, $underline, $strikeOut, $scaleX, $scaleY, $spacing, $angle, $borderStyle, $outline, $shadow, $alignment, $marginL, $marginR, $marginV, $encoding]);
            },
            $assMergedContent
        );
        $com_path = BASE_PATH.'/storage/srt/'.$store_name.'_com.ass';
        // Save merged ASS content to output file
        file_put_contents($com_path, $assMergedContent);
        $video->filters()->custom("ass={$com_path}");
        // 选择输出格式
        $format = new X264('libmp3lame', 'libx264');
        $format->setKiloBitrate(3000);
        // 保存输出文件
        $en = BASE_PATH.'/storage/trans/'.$store_name;
        $video->save($format, $en);
        try {
            $this->s3Service->uploadFile($store_name.'.ass',fopen($com_path,'r'));
            $this->s3Service->uploadFile($store_name,fopen($en,'r'));
        }catch (S3Exception $e){
            throw $e;
        }
        // 删除临时音频文件
        @unlink($outputAudioPath);
        @unlink($audioPath);
        @unlink($videoPath);
        @unlink($srt);
        @unlink($srt_ch);
        @unlink($assPath);
        @unlink($assPath2);
        @unlink($en);
        $transOrder = TransOrder::query()->where('original_video_store_name',$store_name)->first();
        if($transOrder){
            $transOrder->transcribed_video_store_name = $store_name;
            $transOrder->translated_subtitle_store_name = $store_name.'.ass';
            $transOrder->status = 2;
            $transOrder->save();
        }
        $file = Video::query()->where('store_name', $store_name)->first();
        $file->delete();
        return [
            'ass' => $store_name.'.ass',
            'video' => $store_name,
        ];

    }
    function wrapText($text, $maxLineLength = 40) {
        $words = explode(' ', $text);
        $lines = [];
        $currentLine = '';

        foreach ($words as $word) {
            if (strlen($currentLine . ' ' . $word) <= $maxLineLength) {
                $currentLine .= ' ' . $word;
            } else {
                $lines[] = trim($currentLine);
                $currentLine = $word;
            }
        }

        if (!empty($currentLine)) {
            $lines[] = trim($currentLine);
        }

        return implode("\n", $lines);
    }
    function wrapChinese($text, $maxLineLength = 20)
    {
        $wrappedText = '';
        $currentLine = '';
        $currentLineLength = 0;

        $textLength = mb_strlen($text, 'UTF-8');
        for ($i = 0; $i < $textLength; ++$i) {
            $currentChar = mb_substr($text, $i, 1, 'UTF-8');
            $currentLine .= $currentChar;
            $currentLineLength++;

            if ($currentLineLength >= $maxLineLength) {
                $wrappedText .= $currentLine . "\n";
                $currentLine = '';
                $currentLineLength = 0;
            }
        }

        if ($currentLineLength > 0) {
            $wrappedText .= $currentLine;
        }

        return $wrappedText;
    }
    function associateTextWithVoiceSegments($text, $voice_segments) {
        $sentences = preg_split('/[.,!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY);

        $subtitle_data = [];
        foreach ($sentences as $sentence) {
            $best_match = null;
            $best_match_score = INF;

            foreach ($voice_segments as $segment) {
                $segment_text = $segment['text']; // 从 $segment 获取文本，这取决于您的数据结构和处理方式

                // 计算 Levenshtein 和 DTW 距离
                $levenshtein_score = levenshtein_distance($sentence, $segment_text);
                $dtw_score = dtw_distance($sentence, $segment_text);

                // 将两者的距离结合（这里我们简单地将它们相加，但您可以根据需要调整权重）
                $combined_score = $levenshtein_score + $dtw_score;

                // 找到最佳匹配
                if ($combined_score < $best_match_score) {
                    $best_match_score = $combined_score;
                    $best_match = $segment;
                }
            }

            $subtitle_data[] = [
                'start_time' => $best_match['start'],
                'end_time' => $best_match['end'],
                'text' => $sentence
            ];
        }

        return $subtitle_data;
    }
}
