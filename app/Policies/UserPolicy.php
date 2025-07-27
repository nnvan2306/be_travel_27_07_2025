<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Tài khoản ADMIN và STAFF được xem danh sách user
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'staff']);
    }

    /**
     * Admin hoặc chính chủ user mới được xem
     */
    public function view(User $currentUser, User $targetUser)
    {
        // Nếu target là customer → ai cũng xem được
        if ($targetUser->role === 'customer') {
            return true;
        }

        // Chỉ admin mới được xem staff hoặc admin
        return $currentUser->role === 'admin';
    }

    /**
     * Chỉ admin được thêm user
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'staff']);
    }

    /**
     * Chỉ admin hoặc chính chủ mới được sửa
     */
    public function update(User $user, User $model): bool
    {
        return $user->role === 'admin' || $user->id === $model->id;
    }

    /**
     * Chỉ admin được xoá
     */
    public function delete(User $user, User $model): bool
    {
        return in_array($user->role, ['admin', 'staff']);
    }
}