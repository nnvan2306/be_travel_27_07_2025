<?php

namespace App\Http\Controllers;

use App\Models\CompanyContact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CompanyContactController extends Controller
{
    // Lấy thông tin liên hệ công ty (public)
    public function index()
    {
        $contact = CompanyContact::active()->first();
        return response()->json($contact);
    }

    // Lấy thông tin liên hệ công ty (admin)
    public function adminIndex()
    {
        $contacts = CompanyContact::orderBy('created_at', 'desc')->get();
        return response()->json($contacts);
    }

    // Lưu thông tin liên hệ mới (admin)
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'address' => 'nullable|string|max:255',
            'hotline' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'website' => 'nullable|url|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Dữ liệu không hợp lệ', 'errors' => $validator->errors()], 422);
        }

        $contact = CompanyContact::create($request->all());

        return response()->json([
            'message' => 'Thêm thông tin liên hệ thành công',
            'contact' => $contact
        ], 201);
    }

    // Cập nhật thông tin liên hệ (admin)
    public function update(Request $request, $id)
    {
        $contact = CompanyContact::find($id);

        if (!$contact) {
            return response()->json(['message' => 'Không tìm thấy thông tin liên hệ'], 404);
        }

        $validator = Validator::make($request->all(), [
            'address' => 'nullable|string|max:255',
            'hotline' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'website' => 'nullable|url|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Dữ liệu không hợp lệ', 'errors' => $validator->errors()], 422);
        }

        $contact->update($request->all());

        return response()->json([
            'message' => 'Cập nhật thông tin liên hệ thành công',
            'contact' => $contact
        ]);
    }

    // Xem chi tiết thông tin liên hệ (admin)
    public function show($id)
    {
        $contact = CompanyContact::find($id);

        if (!$contact) {
            return response()->json(['message' => 'Không tìm thấy thông tin liên hệ'], 404);
        }

        return response()->json($contact);
    }

    // Xóa thông tin liên hệ (admin)
    public function destroy($id)
    {
        $contact = CompanyContact::find($id);

        if (!$contact) {
            return response()->json(['message' => 'Không tìm thấy thông tin liên hệ'], 404);
        }

        $contact->update(['is_deleted' => 'inactive']);

        return response()->json(['message' => 'Xóa thông tin liên hệ thành công']);
    }
}
