<?php

namespace App\Service;

use Google\ApiCore\ApiException;
use Google\ApiCore\ValidationException;
use Google\Cloud\Speech\V1\RecognitionAudio;
use Google\Cloud\Speech\V1\RecognitionConfig;
use Google\Cloud\Speech\V1\RecognitionConfig\AudioEncoding;
use Google\Cloud\Speech\V1\SpeechClient;
use Google\Cloud\Speech\V1\RecognizeRequest;
use Google\Cloud\Speech\V1\SpeechContext;


class SttService
{
    /**
     * 将音频文件转换为文字
     *
     * @param string $audioPath 音频文件路径
     * @return string|null
     */
    public function convertAudioToText(string $objectName): ?string
    {
        $gcs = new GcsService();
        $audioContent = $gcs->read($objectName);
        $filePath = BASE_PATH.config('google_cloud.key_file_path');
        // 创建 SpeechClient 实例
        try {
            $client = new SpeechClient(
                ['credentials' => $filePath]
            );
        } catch (ValidationException $e) {
            throw $e;
        }

        // 设置识别配置
        $config = new RecognitionConfig([
            'encoding' => AudioEncoding::FLAC,
            'sample_rate_hertz' => 16000,
            'language_code' => 'en-US',
            'max_alternatives' => 1,
            'speech_contexts' => [
                new SpeechContext([
                    'phrases' => ['some', 'sample', 'phrases'],
                ]),
            ],
        ]);

        // 设置音频内容
        $audio = new RecognitionAudio([
            'content' => $audioContent,
        ]);

        // 发送识别请求
        try {
            $response = $client->recognize($config,$audio);
        } catch (ApiException $e) {
            throw $e;
        }

        // 解析识别结果
        $transcription = null;
        foreach ($response->getResults() as $result) {
            $alternatives = $result->getAlternatives();
            foreach ($alternatives as $alternative) {
                $transcription = $alternative->getTranscript();
                break;
            }
            if ($transcription) {
                break;
            }
        }

        // 关闭 SpeechClient
        $client->close();

        return $transcription;
    }
}
