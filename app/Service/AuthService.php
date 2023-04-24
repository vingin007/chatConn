<?php
namespace App\Service;

use App\Exception\BusinessException;
use App\Model\Chat;
use App\Model\User;
use Carbon\Carbon;
use HyperfExtension\Auth\Contracts\AuthManagerInterface;
use HyperfExtension\Hashing\Hash;
use HyperfExtension\Jwt\Contracts\JwtFactoryInterface;

class AuthService
{
    /**
     * @var AuthManagerInterface
     */
    protected $auth;

    public function __construct(AuthManagerInterface $authManager)
    {
        $this->auth = $authManager;
    }

    public function getUser($guard)
    {
        return $this->auth->guard($guard)->user();
    }
    /**
     * 注册并自动登录用户
     *
     * @return array|string
     */
    public function registerAndLogin($guard,$username,$password,$mobile,$email): array
    {
        // 创建用户记录
        $user = User::create([
            'username' => $username,
            'password' => Hash::make($password),
            'mobile' => $mobile,
            'email' => $email,
            'quota' => 0,
            'expire_time' => Carbon::now('Asia/Shanghai')->addMonth(),
            'bind_time' => Carbon::now('Asia/Shanghai'),
        ]);

        // 登录用户
        $token = $this->auth->guard($guard)->login($user);

        $chat = new Chat();
        $chat->name = '默认频道';
        $chat->user_id = $user->id;
        $chat->save();
        return [
            'chat_id' => $chat->id,
            'access_token' => $token,
            'token_type' => 'bearer',
            'expire_in' => make(JwtFactoryInterface::class)->make()->getPayloadFactory()->getTtl()
        ];
    }

    /**
     * 用户登录
     *
     * @param string $mobile
     * @param string $password 密码
     * @param $guard
     * @return array 生成的认证凭证，如果登录失败则返回 null
     */
    public function login(string $email, string $password, $guard): array
    {
        $credentials = ['email' => $email,'password' => $password];
        $token = $this->auth->guard($guard)->attempt($credentials);
        if(!$token) throw new BusinessException(BusinessException::UNAUTHORIZED,'用户名或密码错误');

        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expire_in' => make(JwtFactoryInterface::class)->make()->getPayloadFactory()->getTtl()
        ];
    }

    /**
     * 用户退出登录
     */
    public function logout(): void
    {
        $this->auth->guard('api')->logout();
    }

    /**
     * 刷新认证凭证
     *
     * @return string 新的认证凭证
     */
    public function refresh(): string
    {
        $token = $this->auth->guard('api')->refresh();
        return $token;
    }
}
