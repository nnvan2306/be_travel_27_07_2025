<?php

namespace App\Http\Controllers;

use App\Models\Promotion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PromotionController extends Controller
{
    /**
     * Lấy danh sách mã khuyến mãi
     */
    public function index(Request $request)
    {
        $query = Promotion::query();

        // Lọc theo keyword
        if ($request->has('keyword')) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('code', 'like', "%{$keyword}%")
                    ->orWhere('description', 'like', "%{$keyword}%");
            });
        }

        // Lọc theo trạng thái
        if ($request->has('status') && $request->status !== 'all') {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } else if ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Lọc theo ngày
        if ($request->has('start_date')) {
            $query->whereDate('start_date', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->whereDate('end_date', '<=', $request->end_date);
        }

        // Sắp xếp
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $promotions = $query->paginate($request->input('per_page', 10));

        return response()->json([
            'success' => true,
            'data' => $promotions->items(),
            'meta' => [
                'current_page' => $promotions->currentPage(),
                'last_page' => $promotions->lastPage(),
                'per_page' => $promotions->perPage(),
                'total' => $promotions->total()
            ]
        ]);
    }

    /**
     * Tạo mã khuyến mãi mới
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:20|unique:promotions,code',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:1',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'max_uses' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
            'description' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Kiểm tra thêm giá trị phần trăm
        if ($request->discount_type === 'percentage' && $request->discount_value > 100) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => ['discount_value' => ['Phần trăm giảm giá không thể lớn hơn 100%']]
            ], 422);
        }

        $promotion = Promotion::create([
            'code' => $request->code,
            'discount_type' => $request->discount_type,
            'discount_value' => $request->discount_value,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'max_uses' => $request->max_uses,
            'current_uses' => 0,
            'is_active' => $request->is_active ?? true,
            'description' => $request->description,
        ]);

        return response()->json([
            'success' => true,
            'data' => $promotion,
            'message' => 'Tạo mã khuyến mãi thành công!'
        ], 201);
    }

    /**
     * Hiển thị thông tin chi tiết mã khuyến mãi
     */
    public function show($id)
    {
        $promotion = Promotion::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $promotion
        ]);
    }

    /**
     * Cập nhật mã khuyến mãi
     */
    public function update(Request $request, $id)
    {
        $promotion = Promotion::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:20|unique:promotions,code,' . $id . ',promotion_id',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:1',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'max_uses' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
            'description' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Kiểm tra thêm giá trị phần trăm
        if ($request->discount_type === 'percentage' && $request->discount_value > 100) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => ['discount_value' => ['Phần trăm giảm giá không thể lớn hơn 100%']]
            ], 422);
        }

        $promotion->update([
            'code' => $request->code,
            'discount_type' => $request->discount_type,
            'discount_value' => $request->discount_value,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'max_uses' => $request->max_uses,
            'is_active' => $request->is_active,
            'description' => $request->description,
        ]);

        return response()->json([
            'success' => true,
            'data' => $promotion,
            'message' => 'Cập nhật mã khuyến mãi thành công!'
        ]);
    }

    /**
     * Cập nhật trạng thái mã khuyến mãi
     */
    public function updateStatus(Request $request, $id)
    {
        $promotion = Promotion::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'is_active' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $promotion->update([
            'is_active' => $request->is_active,
        ]);

        return response()->json([
            'success' => true,
            'data' => $promotion,
            'message' => $request->is_active
                ? 'Mã khuyến mãi đã được kích hoạt!'
                : 'Mã khuyến mãi đã được vô hiệu hóa!'
        ]);
    }

    /**
     * Xóa mã khuyến mãi
     */
    public function destroy($id)
    {
        $promotion = Promotion::findOrFail($id);
        $promotion->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa mã khuyến mãi thành công!'
        ]);
    }

    /**
     * Kiểm tra mã khuyến mãi có hợp lệ không
     */
    public function validatePromoCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|exists:promotions,code',
            'order_total' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Mã khuyến mãi không hợp lệ hoặc không tồn tại',
                'errors' => $validator->errors()
            ], 422);
        }

        $promotion = Promotion::where('code', $request->code)->first();

        if (!$promotion->isActive()) {
            $reason = '';
            if (!$promotion->is_active) {
                $reason = 'Mã khuyến mãi đã bị vô hiệu hóa';
            } elseif ($promotion->isExpired()) {
                $reason = 'Mã khuyến mãi đã hết hạn';
            } elseif ($promotion->max_uses !== null && $promotion->current_uses >= $promotion->max_uses) {
                $reason = 'Mã khuyến mãi đã được sử dụng hết';
            }

            return response()->json([
                'success' => false,
                'message' => $reason,
            ], 400);
        }

        $discountAmount = $promotion->calculateDiscount($request->order_total);

        return response()->json([
            'success' => true,
            'data' => [
                'promotion' => $promotion,
                'discount_amount' => $discountAmount,
                'final_amount' => $request->order_total - $discountAmount
            ],
            'message' => 'Mã khuyến mãi hợp lệ!'
        ]);
    }
}
