<?php
namespace App\Service;
use App\Exception\BusinessException;
use Aws\S3\S3Client;
use Aws\TranscribeService\TranscribeServiceClient;
use Hyperf\Di\Annotation\Inject;

class AwsTranscribeService
{
    protected $client;

    #[Inject]
    protected S3Service $s3Service;
    public function __construct()
    {
        $this->client = new TranscribeServiceClient([
            'version' => 'latest',
            'region' => 'ap-northeast-1', // 更改为您的 AWS 区域
            'credentials' => [
                'key' => getenv('AWS_ACCESS_KEY_ID'),
                'secret' => getenv('AWS_SECRET_ACCESS_KEY'),
            ],
        ]);
    }

    public function transcribe_audio_with_amazon_transcribe($store_name,$lang = 'en',$bucket_name = 'audiotr') {
        // 语音识别请求参数
        $uri = "s3://".$bucket_name.'/'.$store_name;
        $jobname = 'trans_'.uniqid().'_'.$store_name;
        $params = [
            'TranscriptionJobName' => $jobname, // 为您的转录任务选择一个名称
            'IdentifyLanguage' => true,
            'Media' => [
                'MediaFileUri' => $uri // 输入音频文件的 URL
            ],
            "OutputBucketName" => $bucket_name,
        ];

        // 开始语音识别任务
        $this->client->startTranscriptionJob($params);

        // 轮询以检查转录任务是否完成
        $isTranscriptionJobComplete = false;
        $transcriptionData = null;
        while (!$isTranscriptionJobComplete) {
            sleep(3); // 等待几秒钟再检查

            $response = $this->client->getTranscriptionJob([
                'TranscriptionJobName' => $jobname,
            ]);

            $transcriptionJobStatus = $response['TranscriptionJob']['TranscriptionJobStatus'];
            if ($transcriptionJobStatus == 'COMPLETED') {
                $isTranscriptionJobComplete = true;
                $resp = $this->s3Service->getContet($response['TranscriptionJob']['TranscriptionJobName'].'.json');
                $transcriptionData = json_decode($resp, true);
            } elseif ($transcriptionJobStatus == 'FAILED') {
                $isTranscriptionJobComplete = true;
                // 处理转录任务失败的情况
                throw new BusinessException('401','Transcription job failed');
            }
        }
        // 返回转录结果
        return $transcriptionData['results']['transcripts'][0]['transcript'];
    }
}
