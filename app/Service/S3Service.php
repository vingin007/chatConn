<?php
namespace App\Service;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;

class S3Service
{
    protected $client;

    public function __construct()
    {
        $this->client = new S3Client([
            'region' => getenv('AWS_REGION'),
            'version' => 'latest',
            'credentials' => [
                'key' => getenv('AWS_ACCESS_KEY_ID'),
                'secret' => getenv('AWS_SECRET_ACCESS_KEY'),
            ],
        ]);
    }

    /**
     * 上传文件到 S3 储存桶
     * @param string $objectKey 文件在储存桶中的唯一标识
     * @param $stream
     * @param string $bucketName 储存桶名称
     * @return bool 是否上传成功
     */
    public function uploadFile(string $objectKey,$stream,string $bucketName = 'smarktalk')
    {
        try {
            $this->client->putObject([
                'Bucket' => $bucketName,
                'Key' => $objectKey,
                'Body'   => $stream,
            ]);
            return true;
        } catch (S3Exception $e) {
            // 处理异常
            return false;
        }
    }

    /**
     * 删除 S3 储存桶中的文件
     * @param string $bucketName 储存桶名称
     * @param string $objectKey 文件在储存桶中的唯一标识
     * @return bool 是否删除成功
     */
    public function deleteFile(string $objectKey,string $bucketName = 'smarktalk')
    {
        try {
            $this->client->deleteObject([
                'Bucket' => $bucketName,
                'Key' => $objectKey,
            ]);
            return true;
        } catch (S3Exception $e) {
            // 处理异常
            return false;
        }
    }

    /**
     * 获取 S3 储存桶中文件的临时链接
     * @param string $bucketName 储存桶名称
     * @param string $objectKey 文件在储存桶中的唯一标识
     * @param int $expiresIn 链接的有效期（秒）
     * @return string|null 文件的临时链接，如果获取失败则返回 null
     */
    public function getFileUrl(string $objectKey, int $expiresIn = 3600,string $bucketName = 'smarktalk')
    {
        try {
            $cmd = $this->client->getCommand('GetObject', [
                'Bucket' => $bucketName,
                'Key' => $objectKey,
            ]);
            $request = $this->client->createPresignedRequest($cmd, "+{$expiresIn} seconds");
            return (string)$request->getUri();
        } catch (S3Exception $e) {
            // 处理异常
            return null;
        }
    }
}
