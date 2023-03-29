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

use HyperfExtension\Auth\Access\HandlesAuthorization;
use HyperfExtension\Auth\Access\Response;
use App\Model\User;
use app\Model\Chat;

class ChatPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can create models.
     *
     * @return bool|Response
     */
    public function create(User $user)
    {
        //
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return bool|Response
     */
    public function delete(User $user, Chat $chat)
    {
        return $user->id === $chat->user_id;
    }
}
