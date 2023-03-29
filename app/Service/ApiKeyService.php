<?php
namespace App\Service;

use App\Exception\BusinessException;
use App\Model\User;
use Hyperf\Redis\Redis;

use Hyperf\Utils\ApplicationContext;

class ApiKeyService
{
    private $redis;

    public function __construct()
    {
        $this->redis = di()->get(Redis::class);
    }

    public function bind($apikey,User $user)
    {
        if ($user->referral_count < 10){
            throw new BusinessException(401,'尚未达到绑定api_key的条件');
        }
        $user->api_key = $apikey;
        $user->api_key_unlocked = true;
        $user->save();
        $this->redis->zadd('active_api_keys', time(), $apikey);
        return true;
    }

    public function remove(User $user)
    {
        $apikey = $user->api_key;
        $user->api_key = '';
        $user->save();
        $this->redis->zrem('active_api_keys', $apikey);
        return true;
    }
    public function getApiKey()
    {
        // 获取平台API Key
        $platformApiKey = getenv('OPENAI_KEY');

        // 获取所有激活状态的用户API Key
        $userApiKeys = $this->redis->zrange('active_api_keys', 0, -1);

        // 计算概率
        $userKeysCount = count($userApiKeys);
        $probability = min(500, $userKeysCount) + 1000;

        // 生成一个随机数
        $randomNumber = rand(1, $probability);

        // 选择API Key
        if ($randomNumber <= $userKeysCount) {
            // 如果随机数小于或等于已激活的用户API Key数量，则选择一个用户API Key
            $selectedApiKey = $this->getRandomUserApiKey($userApiKeys);

            // 更新Redis中所选用户API Key的使用信息
            $this->redis->incr("api_key_usage:{$selectedApiKey}");

            return $selectedApiKey;
        } else {
            // 否则，选择平台API Key
            return $platformApiKey;
        }
    }

    private function getRandomUserApiKey(array $userApiKeys)
    {
        // 获取Redis中用户API Key的使用信息
        $usageInfo = $this->redis->mget(array_map(function ($apiKey) {
            return "api_key_usage:{$apiKey}";
        }, $userApiKeys));

        // 计算所有用户API Key的使用次数总和
        $totalUsage = array_sum(array_map('intval', $usageInfo));

        // 生成一个随机数
        $randomNumber = rand(1, $totalUsage);

        // 选择API Key
        $sum = 0;
        foreach ($userApiKeys as $apiKey) {
            $usage = intval($this->redis->get("api_key_usage:{$apiKey}"));
            $sum += $usage;
            if ($randomNumber <= $sum) {
                // 更新Redis中所选用户API Key的使用信息
                $this->redis->incr("api_key_usage:{$apiKey}");

                return $apiKey;
            }
        }

        // 如果没有选中任何一个用户API Key，则返回平台API Key
        return getenv('OPENAI_KEY');
    }
}
