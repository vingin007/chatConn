<?php
namespace App\Service;

use App\Exception\BusinessException;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Redis\Redis;
use Hyperf\Utils\ApplicationContext;
use Overtrue\EasySms\EasySms;
use Overtrue\EasySms\Exceptions\NoGatewayAvailableException;
use Overtrue\EasySms\PhoneNumber;

class SmsService
{
    protected $redis;

    protected $sms;

    public function __construct()
    {
        $this->sms = new EasySms(config('sms'));
        $this->redis = ApplicationContext::getContainer()->get(Redis::class);
    }

    /**
     * 发送短信验证码
     * @param string $phone 手机号码
     * @return string
     */
    public function sendVerificationCode(string $phone)
    {
        // 生成 6 位随机验证码
        $code = rand(100000, 999999);

        // 发送短信验证码
        try {
            $phone = new PhoneNumber($phone, 86);
            $result = $this->sms->send($phone, [
                'content' => '【签名】您的验证码为：' . $code . '，请勿泄露。',
            ]);

            if ($result['aliyun']['status'] === 'success') {
                // 短信发送成功，将验证码存入 Redis 中并设置过期时间为 5 分钟
                $this->redis->setex('code_'.$phone, 300, $code);
                $key = 'sms_limit:' . date('Ymd') . ':' . $phone;
                $this->redis->incr($key);
                $this->redis->expire($key, 86400);
                return '验证码已发送，请注意查收';
            } else {
                throw new BusinessException('401','验证码发送失败，请重试!');
            }
        } catch (NoGatewayAvailableException|BusinessException $e) {
            // 所有短信网关都不可用
           throw $e;
        }
    }

    /**
     * 校验短信验证码
     * @param string $phone 手机号码
     * @param string $code 短信验证码
     * @return bool 校验结果
     */
    public function verifyVerificationCode(string $phone, string $code)
    {
        // 从 Redis 中获取验证码
        $cachedCode = $this->redis->get($phone);

        if ($cachedCode !== null && $cachedCode == $code) {
            // 验证码校验通过，删除 Redis 中的验证码
            $this->redis->del($phone);
            return true;
        } else {
            // 验证码校验失败
            throw new BusinessException(401,'验证码验证失败');
        }
    }
}
