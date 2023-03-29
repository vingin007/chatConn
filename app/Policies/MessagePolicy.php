<?php

declare(strict_types=1);
/**
 * This file is part of hyperf-extension/auth.
 *
 * @link     https://github.com/hyperf-extension/auth
 * @contact  admin@ilover.me
 * @license  https://github.com/hyperf-extension/auth/blob/master/LICENSE
 */
namespace App\Policies;

use App\Model\Message;
use HyperfExtension\Auth\Access\HandlesAuthorization;
use HyperfExtension\Auth\Annotations\Policy;
use App\Model\User;
use HyperfExtension\Auth\Exceptions\AuthorizationException;

#[Policy(Message::class)]
class MessagePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can create models.
     *
     * @param User $user
     * @return bool
     * @throws AuthorizationException
     */
    public function create(User $user): bool
    {
        if($user->quota <= 0){
            throw new AuthorizationException('你没有条数');
        }
        return $user->quota > 0;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param User $user
     * @return bool
     * @throws AuthorizationException
     */
    public function update(User $user): bool
    {
        if($user->quota <= 0){
            throw new AuthorizationException('你没有可用条数');
        }
        if($user->level < 10){
            throw new AuthorizationException('必须是钻石会员才能调用此功能');
        }
        return $user->quota > 0 && $user->level > 10;
    }

    public function view(User $user,Message $message)
    {
        if($user->id != $message->user_id){
            throw new AuthorizationException('你没有权限查看');
        }
        return true;
    }
}
