<?php

declare(strict_types=1);
namespace App\Controller;

use App\Exception\BusinessException;
use App\Service\AuthService;
use App\Service\MailService;
use OpenApi\Annotations as OA;
use App\Traits\ApiResponseTrait;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\HttpServer\Contract\RequestInterface;
use Overtrue\EasySms\EasySms;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Mailer\MailerInterface;

#[Controller]
class MiniWechatController
{
    use ApiResponseTrait;
    protected $sms;
    #[Inject]
    protected AuthService $authService;
    #[Inject]
    private MailerInterface $mailer;
    /**
     * @OA\Post(
     *     path="/mini_wechat/login",
     *     summary="用户登录",
     *     description="使用手机号和密码进行登录",
     *     operationId="login",
     *     tags={"User"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="请求体",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="email",
     *                 type="string",
     *                 description="邮箱"
     *             ),
     *             @OA\Property(
     *                 property="password",
     *                 type="string",
     *                 description="密码"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="返回登录信息",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="access_token",
     *                 type="string",
     *                 description="访问令牌"
     *             ),
     *             @OA\Property(
     *                 property="token_type",
     *                 type="string",
     *                 description="令牌类型"
     *             ),
     *             @OA\Property(
     *                 property="expire_in",
     *                 type="integer",
     *                 description="过期时间（秒）"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="用户名或密码错误"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="请求参数验证失败"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="服务器内部错误"
     *     )
     * )
     */
    #[RequestMapping(path: 'login')]
    public function login(RequestInterface $request): ResponseInterface
    {
        try {
            if ($request->has('email') && $request->has('password')) {
                $result = $this->authService->login($request->post('email'), $request->post('password'), 'mini');
            } else {
                throw new BusinessException(422,'用户名或密码不能为空');
            }
        }catch (BusinessException $exception){
            return $this->fail($exception->getMessage(),$exception->getErrorCode());
        }
        return $this->success($result);
    }
    /**
     * 用户注册并登录
     *
     * @OA\Post(
     *     path="/mini_wechat/reg",
     *     summary="用户注册并登录",
     *     tags={"User"},
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/x-www-form-urlencoded",
     *             @OA\Schema(
     *                 required={"email","password","confirm_password"},
     *                 @OA\Property(
     *                     property="email",
     *                     type="string",
     *                     description="邮箱",
     *                 ),
     *                 @OA\Property(
     *                     property="password",
     *                     type="string",
     *                     format="password",
     *                     description="密码",
     *                 ),
     *                 @OA\Property(
     *                     property="confirm_password",
     *                     type="string",
     *                     format="password",
     *                     description="确认密码",
     *                 ),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="注册成功",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="chat_id",
     *                 type="integer",
     *                 description="聊天频道 ID"
     *             ),
     *             @OA\Property(
     *                 property="access_token",
     *                 type="string",
     *                 description="JWT 访问令牌"
     *             ),
     *             @OA\Property(
     *                 property="token_type",
     *                 type="string",
     *                 description="令牌类型"
     *             ),
     *             @OA\Property(
     *                 property="expire_in",
     *                 type="integer",
     *                 description="令牌过期时间（秒）"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="参数错误或邮箱格式不正确或密码不一致"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="表单验证失败"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="服务器错误"
     *     )
     * )
     */
    #[RequestMapping(path: 'reg',methods: 'post')]
    public function reg(RequestInterface $request): ResponseInterface
    {
        $email = $request->input('email');
        $password = $request->input('password');
        $confirmPassword = $request->input('confirm_password');

        // 验证邮箱格式
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new BusinessException('邮箱格式不正确');
        }

        // 验证密码确认
        if ($password !== $confirmPassword) {
            throw new BusinessException('密码不一致');
        }
        $result = $this->authService->registerAndLogin('mini',$email,$password,'',$email);
        return $this->success($result);
    }
    /**
     * @OA\Post(
     *     path="/register",
     *     summary="用户注册(聊天注册流程)",
     *     description="用户注册流程",
     *     operationId="register",
     *     tags={"User"},
     *     @OA\RequestBody(
     *         description="请求体",
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 required={"session_id", "input"},
     *                 @OA\Property(
     *                     property="session_id",
     *                     type="string",
     *                     description="会话 ID"
     *                 ),
     *                 @OA\Property(
     *                     property="input",
     *                     type="string",
     *                     description="用户输入"
     *                 ),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="操作成功",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 description="返回消息"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response="422",
     *         description="会话已过期",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 description="错误消息"
     *             )
     *         )
     *     )
     * )
     */
    #[RequestMapping(path: 'register')]
    public function register(RequestInterface $request,ResponseInterface $response)
    {
        $redis = di()->get(\Hyperf\Redis\RedisFactory::class)->get('default');
        if (!$request->has('session_id')){
            $sessionId = uniqid('session_id_');
            $redis->hSet($sessionId,'step','0');
            $result = ['session_id' => $sessionId,'message' => '您好！感谢您选择我们的智能对话服务。系统检测到您当前尚未登录。如果您已经有账号，请返回首页选择登录。如果还没有账号，我们会在当前会话中引导您完成快速注册。请输入一个您心仪的用户名，用户名只能是英文字母或者数字，并且大于六位。'];
            return $this->success($result);
        }
        if(!$redis->exists($request->post('session_id'))){
            return $this->fail('会话已过期请返回后重新进入页面',422);
        }
        $sessionId = $request->post('session_id');
        $input = $request->post('input');

        $step = (int) $redis->hGet($sessionId,'step');

        // 根据当前步骤处理用户输入
        switch ($step) {
            case 0:
                // 处理用户名输入
                if(strlen($input) > 6 && preg_match('/^[a-zA-Z0-9]+$/', $input)) {
                    $redis->hSet($sessionId,'username',$input);
                    $redis->hSet($sessionId,'step','1');
                    $result = ['message' => '太棒了！现在请您设定一个安全的密码（至少6位，包含数字和字母）：'];
                }else{
                    $result = ['message' => '用户名输入有误（至少6位，包含数字和字母），请重新输入：'];
                }
                break;

            case 1:
                // 处理用户名和密码输入
                if(strlen($input) > 6 && preg_match('/^[a-zA-Z0-9]+$/', $input)) {
                    $redis->hSet($sessionId,'password',$input);
                    $redis->hSet($sessionId,'step','2');
                    $result = ['message' => '请您确认一下您的用户名和密码：\n 用户名：'.$redis->hGet($sessionId,'username').' \n 密码：'.$input.' \n 如果信息无误，请回复“确认”，如需修改，请输入“修改用户名”或"修改密码"。'];
                }else{
                    $result = ['message' => '密码输入有误（至少6位，包含数字和字母），请重新输入：'];
                }
                break;

            case 2:
                // 处理修改和确认
                switch ($input){
                    case '确认':
                        $redis->hSet($sessionId,'step','5');
                        $result = ['message' => '接下来，请提供您的邮箱或者手机号码。我们会发送一条包含验证码的信息，以便验证您的身份：'];
                        break;
                    case '修改用户名':
                        $redis->hSet($sessionId,'step','3');
                        $result = ['message' => '请输入您要修改的用户名，用户名只能是英文字母或者数字。'];
                        break;
                    case '修改密码':
                        $redis->hSet($sessionId,'step','4');
                        $result = ['message' => '请输入您要修改的密码（至少6位，包含数字和字母）：'];
                        break;
                    default:
                        $result = ['message' => '您输入的指令有误，请重新输入。'];
                }
                break;

            case 3:
                $redis->hSet($sessionId,'username',$input);
                $redis->hSet($sessionId,'step','2');
                $result = ['message' => '用户名修改成功！请您再次确认一下您的用户名和密码：\n 用户名：'.$redis->hGet($sessionId,'username').' \n 密码：'.$input.' \n 如果信息无误，请回复“确认”，如需修改，请输入“修改用户名”或"修改密码"。'];
                break;

            case 4:
                $redis->hSet($sessionId,'password',$input);
                $redis->hSet($sessionId,'step','2');
                $result = ['message' => '密码修改成功！请您再次确认一下您的用户名和密码：\n 用户名：'.$redis->hGet($sessionId,'username').' \n 密码：'.$input.' \n 如果信息无误，请回复“确认”，如需修改，请输入“修改用户名”或"修改密码"。'];
                break;
            case 5:
                $type = checkInputType($input);
                $code = (string) rand(100000, 999999);
                $redis->setex('code_'.$sessionId, 300, $code);
                if ($type == 'email') {
                    $redis->hSet($sessionId, 'email', $input);
                    $redis->hSet($sessionId, 'step', '6');
                    $mailService = new MailService($this->mailer);
                    $mailService->sendVerificationEmail($input, $code);
                    $result = ['message' => '已经向您的邮箱发送了一条带有验证码的邮件，请查收并告诉我验证码是多少：','code' => $code];
                }elseif ($type == 'mobile') {
                    $redis->hSet($sessionId, 'mobile', $input);
                    $redis->hSet($sessionId, 'step', '6');
                    $key = 'sms_limit:' . date('Ymd') . ':' . $input;
                    $redis->incr($key);
                    $redis->expire($key, 86400);
                    $this->sms = new EasySms(config('sms'));
                    $this->sms->send($input, [
                        'template' => 1755674, // 模板ID
                        'data' => [
                            $code,5
                        ],
                    ]);
                    $result = ['message' => '已经向您的手机发送了一条带有验证码的短信，请查收并告诉我验证码是多少:'];
                }else{
                    $result = ['message' => '您输入的邮箱或手机号码有误，请重新输入。'];
                }
                break;
            case 6:
                $code = $redis->get('code_'.$sessionId);
                if ($input != $code){
                    $result = ['message' => '您输入的验证码有误，请重新输入。'];
                    break;
                }
                $email = $redis->hGet($sessionId,'email') ?? '';
                $mobile = $redis->hGet($sessionId,'mobile') ?? '';
                $result = $this->authService->registerAndLogin('mini',$redis->hGet($sessionId,'username'),$redis->hGet($sessionId,'password'),$mobile,$email);
                $redis->del($sessionId);
                $result['message'] = '恭喜您，注册成功！感谢您的耐心配合。现在您可以和智能对话助理进行交流了。祝您使用愉快！';
                break;

            default:
                $result = ['message' => '您输入的指令有误，请重新输入。'];
                // 其他情况
        }

        // 返回响应给前端
        return $this->success(['message' => $result]);
    }

}