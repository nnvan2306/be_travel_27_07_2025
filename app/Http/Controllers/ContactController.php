<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ContactController extends Controller
{
    // Lấy tất cả contact (chỉ admin và staff)
    public function index()
    {
        $contacts = Contact::active()->orderBy('created_at', 'desc')->get();
        return response()->json($contacts);
    }

    // Lưu contact mới (public)
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // Điều chỉnh các trường theo cấu trúc thực tế của bảng
            'full_name' => 'required|string|max:100',
            'email' => 'required|email',
            'phone' => 'required|string|max:20',
            'message' => 'required|string',
            // Bỏ service_type nếu không có cột này
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Dữ liệu không hợp lệ', 'errors' => $validator->errors()], 422);
        }

        // Chỉ lấy các trường có trong bảng
        $contactData = $request->only(['full_name', 'email', 'phone', 'message', 'status']);

        $contact = Contact::create($contactData);

        return response()->json([
            'message' => 'Gửi liên hệ thành công',
            'contact' => $contact
        ], 201);
    }

    // Xem chi tiết contact (chỉ admin và staff)
    public function show($id)
    {

        $contact = Contact::find($id);

        if (!$contact || $contact->is_deleted === 'inactive') {
            return response()->json(['message' => 'Không tìm thấy liên hệ'], 404);
        }

        return response()->json($contact);
    }

    // Cập nhật trạng thái contact (chỉ admin và staff)
    public function updateStatus(Request $request, $id)
    {
        $contact = Contact::find($id);

        if (!$contact || $contact->is_deleted === 'inactive') {
            return response()->json(['message' => 'Không tìm thấy liên hệ'], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,processed,completed',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Dữ liệu không hợp lệ', 'errors' => $validator->errors()], 422);
        }

        $contact->status = $request->status;
        $contact->save();

        return response()->json([
            'message' => 'Cập nhật trạng thái thành công',
            'contact' => $contact
        ]);
    }

    // Xóa mềm contact (chỉ admin)
    public function softDelete($id)
    {
        $contact = Contact::find($id);

        if (!$contact) {
            return response()->json(['message' => 'Không tìm thấy liên hệ'], 404);
        }

        $contact->is_deleted = 'inactive';
        $contact->save();

        return response()->json(['message' => 'Xóa liên hệ thành công']);
    }
}
