<?php

namespace App\Service;

use Google\ApiCore\ApiException;
use Google\ApiCore\ValidationException;
use Google\Cloud\TextToSpeech\V1\AudioConfig;
use Google\Cloud\TextToSpeech\V1\AudioEncoding;
use Google\Cloud\TextToSpeech\V1\SynthesisInput;
use Google\Cloud\TextToSpeech\V1\TextToSpeechClient;
use Google\Cloud\TextToSpeech\V1\VoiceSelectionParams;



class TtsService
{
    /**
     * 将音频文件转换为文字
     *
     * @param string $content
     * @param $objectName
     * @return string|null
     * @throws ApiException
     * @throws ValidationException
     */
    public function convertTextToAudio(string $content,$objectName): array|string|null
    {
        // 读取环境变量中的 Google Cloud Platform 服务账户信息
        $filePath = BASE_PATH.config('google_cloud.key_file_path');
        // 实例化 TextToSpeechClient
        $client = new TextToSpeechClient(
            [ 'credentials' => $filePath]
        );

        // 设置语音合成选项
        $audioConfig = new AudioConfig([
            'audio_encoding' => AudioEncoding::LINEAR16,
            'sample_rate_hertz' => 16000
        ]);

        // 设置要合成的文本和语音类型
        $voice = new VoiceSelectionParams([
            'language_code' => 'en-US',
            'name' => 'en-US-Wavenet-D',
        ]);
        $input = new SynthesisInput();
        $input->setText($content);

        // 调用 synthesizeSpeech 方法生成语音文件
        try {
            $response = $client->synthesizeSpeech($input, $voice, $audioConfig);
        } catch (ApiException $e) {
            throw $e;
        }
        $audioContent = $response->getAudioContent();
        $gcs = new GcsService();
        $gcs->upload($objectName,$audioContent);
        return ['filename' => $objectName,'url' => $gcs->get($objectName)];
    }
}
