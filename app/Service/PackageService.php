<?php

namespace App\Service;

use App\Model\Package;
use Hyperf\Cache\Annotation\CacheEvict;
use Hyperf\Cache\Annotation\Cacheable;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Redis\Redis;
use Psr\SimpleCache\CacheInterface;

class PackageService
{
    private $redis;
    public function __construct()
    {
        $this->redis = di()->get(Redis::class);
    }


    #[Cacheable(prefix: "packages", ttl: 3600, value: "all")]
    public function getAll(): ?array
    {
        return Package::all()->toArray();
    }

    #[CacheEvict(prefix: "packages", value: "all")]
    public function create(array $data): Package
    {
        $package = new Package();
        $package->name = $data['name'];
        $package->quota = $data['quota'];
        $package->level = $data['level'];
        $package->duration = $data['duration'];
        $package->price = $data['price'];
        $package->save();

        // 初始化价格缓存队列
        for ($i = 0; $i < 10; $i++) {
            $amount = $data['price'] - $i / 100;
            $this->redis->lPush("order:amounts:{$package->id}", $amount);
        }


        return $package;
    }

    #[CacheEvict(prefix: "packages", value: "all")]
    public function delete(int $id): bool
    {
        $package = Package::find($id);
        if (!$package) {
            return false;
        }

        // 删除价格缓存队列
        $this->redis->lTrim("packages:{$package->id}:price_queue",1,0);

        $package->delete();

        return true;
    }
}
