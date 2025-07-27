<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class PaymentMethodController extends Controller
{
    /**
     * Liệt kê tất cả payment methods
     */
    public function index(Request $request): JsonResponse
    {
        $query = PaymentMethod::active();

        // Tìm kiếm theo tên
        if ($request->has('search')) {
            $query->where('name', 'LIKE', '%' . $request->search . '%');
        }

        $paymentMethods = $query->orderBy('name', 'asc')->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $paymentMethods
        ]);
    }

    /**
     * Tạo mới payment method
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100|unique:payment_methods,name',
            'description' => 'nullable|string'
        ], [
            'name.required' => 'Tên phương thức thanh toán là bắt buộc',
            'name.max' => 'Tên phương thức thanh toán không được vượt quá 100 ký tự',
            'name.unique' => 'Tên phương thức thanh toán đã tồn tại'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $validator->errors()
            ], 422);
        }

        $paymentMethod = PaymentMethod::create([
            'name' => $request->name,
            'description' => $request->description,
            'is_deleted' => 'active'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tạo phương thức thanh toán thành công',
            'data' => $paymentMethod
        ], 201);
    }

    /**
     * Lấy chi tiết payment method theo ID
     */
    public function show(string $id): JsonResponse
    {
        $paymentMethod = PaymentMethod::find($id);

        if (!$paymentMethod) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy phương thức thanh toán'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $paymentMethod
        ]);
    }

    /**
     * Cập nhật payment method
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $paymentMethod = PaymentMethod::find($id);

        if (!$paymentMethod) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy phương thức thanh toán'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:100|unique:payment_methods,name,' . $id,
            'description' => 'nullable|string',
            'is_deleted' => 'sometimes|in:active,inactive'
        ], [
            'name.max' => 'Tên phương thức thanh toán không được vượt quá 100 ký tự',
            'name.unique' => 'Tên phương thức thanh toán đã tồn tại'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $validator->errors()
            ], 422);
        }

        $paymentMethod->update($request->only(['name', 'description', 'is_deleted']));

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật phương thức thanh toán thành công',
            'data' => $paymentMethod
        ]);
    }

    /**
     * Xóa mềm payment method
     */
    public function softDelete(string $id): JsonResponse
    {
        $paymentMethod = PaymentMethod::find($id);

        if (!$paymentMethod) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy phương thức thanh toán'
            ], 404);
        }

        if ($paymentMethod->is_deleted === 'inactive') {
            return response()->json([
                'success' => false,
                'message' => 'Phương thức thanh toán đã bị ẩn trước đó'
            ], 409);
        }

        $paymentMethod->update(['is_deleted' => 'inactive']);

        return response()->json([
            'success' => true,
            'message' => 'Ẩn phương thức thanh toán thành công'
        ]);
    }

    /**
     * Xóa vĩnh viễn payment method
     */
    public function destroy(string $id): JsonResponse
    {
        $paymentMethod = PaymentMethod::find($id);

        if (!$paymentMethod) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy phương thức thanh toán'
            ], 404);
        }

        $paymentMethod->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa vĩnh viễn phương thức thanh toán thành công'
        ]);
    }

    /**
     * Liệt kê payment methods đã xóa
     */
    public function trashed(): JsonResponse
    {
        $paymentMethods = PaymentMethod::inactive()
            ->orderBy('name', 'asc')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $paymentMethods
        ]);
    }

    /**
     * Khôi phục payment method đã xóa
     */
    public function restore(string $id): JsonResponse
    {
        $paymentMethod = PaymentMethod::where('id', $id)
            ->where('is_deleted', 'inactive')
            ->first();

        if (!$paymentMethod) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy phương thức thanh toán đã ẩn'
            ], 404);
        }

        $paymentMethod->update(['is_deleted' => 'active']);

        return response()->json([
            'success' => true,
            'message' => 'Khôi phục phương thức thanh toán thành công',
            'data' => $paymentMethod
        ]);
    }

    /**
     * Thống kê payment methods
     */
    public function statistics(): JsonResponse
    {
        $stats = [
            'total_payment_methods' => PaymentMethod::count(),
            'active_payment_methods' => PaymentMethod::active()->count(),
            'inactive_payment_methods' => PaymentMethod::inactive()->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}