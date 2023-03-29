<?php
namespace App\Service;

use Google\Cloud\Storage\StorageClient;
use Google\Cloud\Storage\StorageObject;
use Psr\Http\Message\StreamInterface;

class GcsService
{
    protected $storage;
    protected $bucketName;
    public function __construct()
    {
        $this->storage = new StorageClient([
            'projectId' => config('google_cloud.project_id'),
            'keyFilePath' => BASE_PATH.config('google_cloud.key_file_path')
        ]);
        $this->bucketName = config('google_cloud.bucket_name');
    }

    /**
     * 上传文件到GCS存储桶
     *
     * @param string $objectName 存储对象名称
     * @param string|resource|StreamInterface $data 要上传的数据
     * @param array $options 可选参数，例如自定义元数据等
     * @return StorageObject 上传成功的对象
     */
    public function upload($objectName, $data, array $options = [])
    {
        $bucket = $this->storage->bucket($this->bucketName);
        $object = $bucket->upload($data, array_merge([
            'name' => $objectName
        ], $options));
        return $object;
    }

    /**
     * 读取GCS存储桶中的对象数据
     *
     * @param string $bucketName 存储桶名称
     * @param string $objectName 存储对象名称
     * @return string|null 对象数据，如果对象不存在则返回null
     */
    public function read($objectName)
    {
        $bucket = $this->storage->bucket($this->bucketName);
        $object = $bucket->object($objectName);
        if (!$object->exists()) {
            return null;
        }
        return $object->downloadAsString();
    }

    /**
     * @param $objectName
     * @return string|null
     */
    public function get($objectName): ?string
    {
        $bucket = $this->storage->bucket($this->bucketName);
        $object = $bucket->object($objectName);
        if (!$object->exists()) {
            return null;
        }
        $expiry = strtotime('+7 days');
        return $object->signedUrl($expiry);
    }
    /**
     * 删除GCS存储桶中的对象
     *
     * @param string $bucketName 存储桶名称
     * @param string $objectName 存储对象名称
     * @return bool 删除成功返回true，否则返回false
     */
    public function delete($objectName)
    {
        $bucket = $this->storage->bucket($this->bucketName);
        $object = $bucket->object($objectName);
        return $object->delete();
    }
}
