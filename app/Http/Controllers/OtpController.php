<?php

namespace App\Http\Controllers;

use App\Models\Otp;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Mail\OtpMail;
use Illuminate\Support\Facades\Mail;

class OtpController extends Controller
{

    public function sendOtp(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'method' => 'required|in:email,phone',
        ]);

        // Giới hạn gửi
        $lastOtp = Otp::where('user_id', $request->user_id)
            ->where('method', $request->method)
            ->latest()
            ->first();

        if ($lastOtp && $lastOtp->created_at->diffInSeconds(now()) < 30) {
            return response()->json([
                'message' => 'Vui lòng chờ trước khi gửi lại mã OTP'
            ], 429);
        }

        // Xoá mã cũ
        Otp::where('user_id', $request->user_id)
            ->where('method', $request->method)
            ->delete();

        // Tạo mã mới
        $code = rand(100000, 999999);
        $expiresAt = Carbon::now()->addMinutes(2);

        Otp::create([
            'user_id' => $request->user_id,
            'method' => $request->method,
            'code' => $code,
            'expires_at' => $expiresAt,
        ]);

        $user = User::find($request->user_id);

        if ($request->method === 'phone') {
            Log::info("OTP gửi đến SĐT {$user->phone}: {$code}");
            // Bạn có thể tích hợp SMS ở đây nếu cần
        } else {
            // Gửi email thật
            Mail::to($user->email)->send(new OtpMail($code));
            Log::info("OTP gửi đến Email {$user->email}: {$code}");
        }

        return response()->json([
            'message' => 'OTP đã được gửi thành công',
            'expired_at' => $expiresAt->toDateTimeString(),
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'method' => 'required|in:email,phone',
            'code' => 'required|digits:6',
        ]);

        $otp = Otp::where('user_id', $request->user_id)
            ->where('method', $request->method)
            ->where('code', $request->code)
            ->where('expires_at', '>=', now())
            ->latest()
            ->first();

        if (!$otp) {
            return response()->json([
                'message' => 'Mã OTP không hợp lệ hoặc đã hết hạn'
            ], 422);
        }

        $user = User::find($request->user_id);
        $user->is_verified = true;
        $user->save();

        // Xoá OTP sau khi xác thực
        $otp->delete();

        return response()->json([
            'message' => 'Xác thực thành công',
            'user' => $user,
        ]);
    }
}