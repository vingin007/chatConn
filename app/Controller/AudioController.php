<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exception\BusinessException;
use App\Middleware\Auth\RefreshTokenMiddleware;
use App\Service\AudioService;
use App\Traits\ApiResponseTrait;
use Aws\S3\Exception\S3Exception;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\Middlewares;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use HyperfExtension\Auth\AuthManager;
use App\Model\Video;

#[Controller]
#[Middlewares([
    RefreshTokenMiddleware::class
])]
class AudioController
{
    use ApiResponseTrait;
    #[Inject]
    protected AuthManager $auth;
    #[Inject]
    protected AudioService $audioService;
    #[RequestMapping(path: 'upload', methods: 'post')]
    public function upload(RequestInterface $request, ResponseInterface $response)
    {
        $user = $this->auth->guard('mini')->user();
        $file = $request->file('file');
        try {
            $result = $this->audioService->justUpload($user,$file);
            $this->audioService->deleteFileAfterTime($result['id']);
            return $this->success($result);
        } catch (BusinessException $e) {
            return $this->fail($e->getMessage(),$e->getCode());
        }
    }
    #[RequestMapping(path: 'trans', methods: 'post')]
    public function trans(RequestInterface $request, ResponseInterface $response)
    {
        /*$ffmpeg = FFMpeg::create();
        $videoPath = BASE_PATH.'/runtime/storage/pre_6440c743e4e9f.mp4';
        $ass = BASE_PATH.'/storage/srt/pre_6440c743e4e9f.mp4_com.ass';
        // 打开视频文件
        $video = $ffmpeg->open($videoPath);
        $video->filters()->custom("ass={$ass}");
        // 选择输出格式
        $format = new X264();
        // 保存输出文件
        $en = BASE_PATH.'/storage/trans/2.mp4';
        $video->save($format, $en);*/
        $file_id = $request->input('file_id','');
        $is_trans = $request->input('is_trans',1);
        $lang = $request->input('lang','chinese');
        try {
            $user = $this->auth->guard('mini')->user();
            $file = Video::query()->where('id', $file_id)->first();
            if(empty($file)){
                throw new BusinessException(400, '文件不存在');
            }
            if($file->status != 1){
                throw new BusinessException(401,'订单尚未支付，无法进行转录操作');
            }
            $result = $this->audioService->uploadAndText($file->store_name,$user,$lang,boolval($is_trans));
            $file->delete();
            return $this->success($result);
        } catch (BusinessException|S3Exception $e) {
            return $this->fail($e->getMessage(),$e->getCode());
        }
    }
}
