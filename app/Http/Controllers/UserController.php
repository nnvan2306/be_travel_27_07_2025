<?php

namespace App\Http\Controllers;

use App\Models\User;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

class UserController extends Controller
{
    use AuthorizesRequests;

    // Danh sách user (chỉ admin mới xem được)
    public function index()
    {
        $users = User::whereIn('role', ['admin', 'staff','customer'])->get()->map(function ($user) {
            $user->avatar_url = $user->avatar ? asset('' . $user->avatar) : null;
            return $user;
        });

        return response()->json($users);
    }


    // Chi tiết user
    public function show($id)
    {
        $user = User::where('is_deleted', 'active')->find($id);
        if (!$user)
            return response()->json(['message' => 'Không tìm thấy user'], 404);
        $this->authorize('view', $user);

        $user->avatar_url = $user->avatar ? asset('storage/' . $user->avatar) : null;
        return response()->json($user);
    }

    // Tạo user mới (admin)
    public function store(Request $request)
    {
        $this->authorize('create', User::class);

        $request->validate([
            'full_name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|unique:users,phone',
            'password' => 'required|string|min:6',
            'role' => 'in:customer,staff,admin',
            'avatar' => 'nullable|image|max:2048'
        ]);

        $avatarPath = null;
        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
        }

        $user = User::create([
            'full_name' => $request->full_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'avatar' => $avatarPath,
            'role' => $request->role ?? 'customer'
        ]);

        $user->avatar_url = $avatarPath ? asset('storage/' . $avatarPath) : null;
        return response()->json(['message' => 'Thêm tài khoản thành công', 'user' => $user], 201);
    }
    

    // Cập nhật user
    public function update(Request $request, $id)
    {
        $user = User::where('is_deleted', 'active')->find($id);
        if (!$user) {
            return response()->json(['message' => 'Không tìm thấy user'], 404);
        }

        $this->authorize('update', $user);

        $request->validate([
            'full_name' => 'sometimes|string|max:100',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'phone' => 'sometimes|string|unique:users,phone,' . $id,
            'password' => 'nullable|string|min:6',
            'role' => 'in:customer,staff,admin',
            'avatar' => 'nullable|image|max:2048',
        ]);

        $avatarPath = null;
        if ($request->hasFile('avatar')) {
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }

            $avatarPath = $request->file('avatar')->store('avatars', 'public');
            $user->avatar = $avatarPath;
        }

        $user->fill($request->only([
            'full_name',
            'email',
            'phone',
            'role',
        ]));

        if ($request->filled('password')) {
            $user->password = Hash::make($request->input('password'));
        }

        $user->save();

        $user->avatar_url = $avatarPath ? asset('storage/' . $avatarPath) : null;

        return response()->json([
            'message' => 'Cập nhật thành công',
            'user' => $user
        ]);
    }



    // Xóa mềm user: chỉ đổi trạng thái is_deleted
    public function softDelete($id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'Không tìm thấy user'], 404);
        }

        $currentUser = Auth::user();

        // Staff chỉ được xóa mềm customer
        if ($currentUser->role === 'staff' && $user->role !== 'customer') {
            return response()->json(['message' => 'Bạn không có quyền chuyển trạng thái tài khoản này'], 403);
        }

        // Kiểm tra quyền update/xóa
        if ($currentUser->role !== 'admin' && $currentUser->role !== 'staff') {
            return response()->json(['message' => 'Bạn không có quyền'], 403);
        }

        // Chuyển trạng thái
        $user->is_deleted = $user->is_deleted === 'active' ? 'inactive' : 'active';
        $user->save();

        return response()->json(['message' => 'Đã chuyển trạng thái tài khoản thành công', 'user' => $user]);
    }

    //Danh sách user đã xóa mềm
    public function trashed()
    {
        $this->authorize('viewAny', User::class);
        $users = User::where('is_deleted', 'inactive')->get()->map(function ($user) {
            $user->avatar_url = $user->avatar ? asset('storage/' . $user->avatar) : null;
        });

        return response()->json($users);
    }    

    //Khôi phục user đã xóa mềm
    public function restore($id){
        $user = User::where('is_deleted', 'inactive')->find($id);
        if (!$user){
            return response()->json(['message' => 'Không tìm thấy user bị xóa'], 404);
        }
        $this->authorize('update', $user);
        $user->is_deleted = 'active';
        $user->save();
        
        return response()->json(['message' => 'Khôi phục thành công', 'user'=>$user]);
    }

    // Xóa vĩnh viễn user
    public function destroy($id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'Không tìm thấy user'], 404);
        }

        $currentUser = Auth::user();

        // Chỉ admin được xóa vĩnh viễn
        if ($currentUser->role !== 'admin') {
            return response()->json(['message' => 'Chỉ admin mới được xóa vĩnh viễn tài khoản'], 403);
        }

        $this->authorize('delete', $user);

        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }
        $user->delete();

        return response()->json(['message' => 'Xóa user thành công']);
    }

    // Xem thông tin profile của user hiện tại
    public function profile(Request $request)
    {
        return response()->json($request->user());
    }

    // Cập nhật thông tin cá nhân
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'full_name' => 'nullable|string|max:100',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|unique:users,phone,' . $user->id,
            'password' => 'nullable|string|min:6',
            'avatar' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('avatar')) {
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }
            $user->avatar = $request->file('avatar')->store('avatars', 'public');
        }

        if ($request->filled('full_name')) {
            $user->full_name = $request->full_name;
        }

        if ($request->filled('phone')) {
            $user->phone = $request->phone;
        }

        if ($request->filled('email') && $request->email !== $user->email) {
            $user->email = $request->email;
            $user->is_verified = false;
        }

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        $user->avatar_url = $user->avatar ? asset('storage/' . $user->avatar) : null;

        return response()->json([
            'message' => 'Cập nhật thông tin cá nhân thành công!',
            'user' => $user
        ]);
    }

}