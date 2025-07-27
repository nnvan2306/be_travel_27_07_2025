<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $isAdmin = Auth::check();

        $validator = Validator::make($request->all(), [
            'full_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['required', 'regex:/^(0|\+84)[0-9]{9,10}$/', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:6'],
            'role' => ['nullable', 'in:customer,staff,admin'],
            'avatar' => ['nullable', 'image', 'max:2048']
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $validator->errors(),
            ], 422);
        }

        $avatarPath = null;
        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
        }

        $user = User::create([
            'full_name' => $request->full_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => $request->role ?? 'customer',
            'is_verified' => $isAdmin,
            'avatar' => $avatarPath,
        ]);

        $user->avatar_url = $avatarPath ? asset('storage/' . $avatarPath) : null;

        return response()->json([
            'message' => $isAdmin ? 'Tạo tài khoản thành công!' : 'Đăng ký thành công! Vui lòng xác thực tài khoản',
            'user' => $user,
            'need_verification' => !$isAdmin,
        ], 201);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'login' => ['required', 'string'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $validator->errors(),
            ], 422);
        }

        $login = $request->input('login');

        if (!filter_var($login, FILTER_VALIDATE_EMAIL) && !preg_match('/^(0|\+84)[0-9]{9,10}$/', $login)) {
            return response()->json([
                'message' => 'Định dạng không hợp lệ (email hoặc số điện thoại)',
            ], 422);
        }

        $user = User::where('email', $login)
            ->orWhere('phone', $login)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Thông tin đăng nhập không chính xác',
            ], 401);
        }

        if ($user->is_deleted === 'inactive') {
            return response()->json([
                'message' => 'Tài khoản đã bị vô hiệu hóa',
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Đăng nhập thành công',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ]);
    }

    public function logout(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json(['message' => 'Người dùng chưa đăng nhập'], 401);
            }

            $accessToken = $user->currentAccessToken();

            if ($accessToken) {
                $accessToken->delete();
            }

            return response()->json(['message' => 'Đăng xuất thành công']);
        } catch (\Throwable $e) {
            Log::error('Logout failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Đăng xuất thất bại',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
