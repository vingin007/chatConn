<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\BusinessException;
use App\Model\Package;
use App\Model\User;
use Carbon\Carbon;
use Hyperf\Di\Annotation\Inject;

class UserService
{
    #[Inject]
    protected SmsService $smsService;
    /**
     * 创建用户.
     */
    public function createUser(array $data): ?User
    {
        // 验证邮箱是否已经存在
        $exists = User::query()->where('email', $data['email'])->exists();
        if ($exists) {
            throw new BusinessException('401','邮箱已经被注册');
        }

        // 创建用户
        $user = new User();
        $user->email = $data['email'];
        $user->password = md5($data['password']);
        $user->openid = $data['openid'];
        $user->nickname = $data['nickname'];
        $user->avatar = $data['avatar'];
        $user->save();
        //创建用户的同时创建频道
        return $user;
    }

    public function assignPackage(User $user, Package $package): bool
    {
        // 给用户分配package
        $user->package_id = $package->id;
        $user->quota = $package->quota;
        $user->duration = $package->duration;
        $user->save();

        return true;
    }

    public function bindMobile(User $user,$mobile,$code,$password): User
    {
        try {
            $this->smsService->verifyVerificationCode($mobile,$code);
        }catch (BusinessException $e){
            throw $e;
        }
        $user->quota = 5;
        $user->expire_time = Carbon::now('Asia/Shanghai')->addMonth();
        $user->mobile = $mobile;
        $user->password = $mobile;
        $user->bind_time = Carbon::now('Asia/Shanghai');
        $user->save();
        return $user;
    }

    /**
     * 根据id获取用户.
     */
    public function getUserById(int $userId): ?User
    {
        return User::find($userId);
    }
}

