<?php
namespace App\Service;
use Aws\Polly\PollyClient;
use Aws\S3\Exception\S3Exception;

class PollyService
{
    protected $client;

    public function __construct()
    {
        $this->client = new PollyClient([
            'region' => 'ap-northeast-1',
            'version' => 'latest',
            'credentials' => [
                'key' => getenv('AWS_ACCESS_KEY_ID'),
                'secret' => getenv('AWS_SECRET_ACCESS_KEY'),
            ],
        ]);
    }

    /**
     * 将文本转换为语音
     * @param string $text 要转换的文本
     * @param string $outputFormat 语音输出格式（mp3、ogg_vorbis、pcm）
     * @param string $voiceId 语音样式（例如：Joanna、Matthew、Mizuki 等）
     * @return string|null 转换后的语音内容，如果转换失败则返回 null
     */
    public function textToSpeech(string $text, string $outputFormat = 'mp3', string $voiceId = 'Joanna')
    {
        try {
            $result = $this->client->synthesizeSpeech([
                'OutputFormat' => $outputFormat,
                'Text' => $text,
                'VoiceId' => $voiceId,
            ]);
            return (string)$result->get('AudioStream')->getContents();
        } catch (S3Exception $e) {
            // 处理异常
            return null;
        }
    }
}
