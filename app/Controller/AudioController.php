<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exception\BusinessException;
use App\Service\AudioService;
use App\Traits\ApiResponseTrait;
use Aws\S3\Exception\S3Exception;
use GuzzleHttp\Psr7\Utils;
use Hyperf\Context\ApplicationContext;
use Hyperf\Di\Annotation\Inject;
use GuzzleHttp\Psr7\Stream;
use Hyperf\Filesystem\FilesystemFactory;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use HyperfExtension\Auth\AuthManager;
use App\Model\Video;

#[Controller]
class AudioController
{
    use ApiResponseTrait;
    #[Inject]
    protected AuthManager $auth;
    #[Inject]
    protected AudioService $audioService;
    /**
     * @OA\Post(
     *     path="/audio/upload",
     *     summary="Upload audio file",
     *     description="Uploads an audio file and returns the result.",
     *     tags={"Audio"},
     *     @OA\RequestBody(
     *         description="Audio file to upload",
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="file",
     *                     description="Audio file",
     *                     type="file",
     *                 ),
     *             ),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(ref="#/components/schemas/Video"),
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request",
     *         @OA\JsonContent(ref="#/components/schemas/BadRequestResponse"),
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse"),
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(ref="#/components/schemas/InternalServerErrorResponse"),
     *     ),
     * )
     */
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
    /**
     * @OA\Post(
     *     path="/audio/trans",
     *     summary="Transcribe audio file",
     *     description="将上传后并且生成订单的视频转码",
     *     tags={"Audio"},
     *     @OA\RequestBody(
     *         description="Request parameters",
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="file_id",
     *                     description="ID of the audio file to transcribe",
     *                     type="string",
     *                 ),
     *                 @OA\Property(
     *                     property="is_trans",
     *                     description="是否保留原字幕",
     *                     type="boolean",
     *                     default=true,
     *                 ),
     *                 @OA\Property(
     *                     property="lang",
     *                     description="指定目标语言",
     *                     type="string",
     *                     default="chinese",
     *                     enum={"chinese", "english", "japanese", "korean", "french", "german", "spanish", "portuguese", "italian", "russian", "arabic", "hindi"},
     *                 ),
     *             ),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="ass", type="string", description="Subtitle file name"),
     *             @OA\Property(property="video", type="string", description="Transcribed video file name"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request",
     *         @OA\JsonContent(ref="#/components/schemas/BadRequestResponse"),
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse"),
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(ref="#/components/schemas/InternalServerErrorResponse"),
     *     ),
     * )
     */
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
        $margin = $request->input('margin_v',30);
        $font_size = $request->input('font_size',14);
        try {
            $user = $this->auth->guard('mini')->user();
            $file = Video::query()->where('id', $file_id)->first();
            if(empty($file)){
                throw new BusinessException(400, '文件不存在');
            }
            if($file->status != 1){
                throw new BusinessException(401,'订单尚未支付，无法进行转录操作');
            }
            $result = $this->audioService->uploadAndText($file,$user,$font_size,$margin,$lang,boolval($is_trans));
            return $this->success(['message' => '转录任务开始，请稍后']);
        } catch (BusinessException|S3Exception $e) {
            return $this->fail($e->getMessage(),$e->getCode());
        }
    }
    #[RequestMapping(path: 'get_audio', methods: 'get')]
    public function getAudio(RequestInterface $request, ResponseInterface $response)
    {
        $file_id = $request->input('id','');
        try {
            $user = $this->auth->guard('mini')->user();
            $file = Video::query()->where('id', $file_id)->first();
            if(empty($file)){
                throw new BusinessException(400, '文件不存在');
            }
            $filesystem = ApplicationContext::getContainer()->get(FilesystemFactory::class)->get('local');
            if ($filesystem->has($file->store_name) === false) {
                throw new BusinessException(400, '文件不存在');
            }
            $stream = $filesystem->readStream($file->store_name);

            return $response
                ->withHeader('Content-Type', 'application/octet-stream')
                ->withHeader('Content-Disposition', 'attachment; filename="' . $file->store_name . '"')
                ->withBody($body = Utils::streamFor($stream));
        } catch (BusinessException|S3Exception $e) {
            return $this->fail($e->getMessage(),$e->getCode());
        }
    }
    #[RequestMapping(path: 'recreate',methods: 'post')]
    public function recreate(RequestInterface $request, ResponseInterface $response)
    {
        $file_id = $request->input('id','');
        $is_trans = $request->input('is_trans',1);
        $margin = $request->input('margin_v',30);
        $font_size = $request->input('font_size',14);
        $oriwarp = $request->input('ori_warp',40);
        $transwarp = $request->input('trans_warp',15);
        $lang = $request->input('lang','chinese');
        $file = Video::query()->where('id', $file_id)->first();
        if(empty($file)){
            throw new BusinessException(400, '文件不存在');
        }
        $filesystem = ApplicationContext::getContainer()->get(FilesystemFactory::class)->get('local');
        if ($filesystem->has($file->store_name) === false) {
            throw new BusinessException(400, '文件不存在');
        }
        $videoPath = BASE_PATH.'/runtime/storage/'.$file->store_name;
        $this->audioService->recreate($file->message,$file->store_name,$videoPath,$lang,$font_size,$margin,$oriwarp,$transwarp,$is_trans);
        return $this->success(['message' => '已开始重新烧制，请稍后']);
    }
}
