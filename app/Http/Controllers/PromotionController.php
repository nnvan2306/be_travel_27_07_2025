<?php

namespace App\Http\Controllers;

use App\Models\Promotion;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PromotionController extends Controller
{
    // Danh sách promotions (chỉ active)
    public function index(Request $request)
    {
        try {
            $query = Promotion::active();

            // Tìm kiếm theo code
            if ($request->has('search')) {
                $query->where('code', 'like', '%' . $request->search . '%');
            }

            // Lọc theo applies_to
            if ($request->has('applies_to')) {
                $query->where('applies_to', $request->applies_to);
            }

            // Lọc theo trạng thái hiệu lực
            if ($request->has('status')) {
                $today = now()->toDateString();
                if ($request->status === 'valid') {
                    $query->where('valid_from', '<=', $today)
                          ->where('valid_to', '>=', $today);
                } elseif ($request->status === 'expired') {
                    $query->where('valid_to', '<', $today);
                }
            }

            $promotions = $query->paginate($request->get('per_page', 10));

            return response()->json([
                'success' => true,
                'data' => $promotions
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy danh sách promotions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Chi tiết promotion
    public function show($id)
    {
        try {
            $promotion = Promotion::find($id);

            if (!$promotion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Promotion không tồn tại'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $promotion
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy chi tiết promotion',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Tạo promotion mới
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|max:50|unique:promotions,code',
                'discount' => 'required|numeric|min:0|max:100',
                'max_uses' => 'required|integer|min:1',
                'valid_from' => 'required|date|after_or_equal:today',
                'valid_to' => 'required|date|after:valid_from',
                'applies_to' => ['required', Rule::in(['tour', 'combo', 'hotel', 'transport', 'all'])]
            ], [
                'code.required' => 'Mã khuyến mãi là bắt buộc',
                'code.unique' => 'Mã khuyến mãi đã tồn tại',
                'discount.required' => 'Giá trị giảm giá là bắt buộc',
                'discount.min' => 'Giá trị giảm giá phải lớn hơn 0',
                'discount.max' => 'Giá trị giảm giá không được vượt quá 100%',
                'max_uses.required' => 'Số lần sử dụng tối đa là bắt buộc',
                'valid_from.required' => 'Ngày bắt đầu là bắt buộc',
                'valid_from.after_or_equal' => 'Ngày bắt đầu phải từ hôm nay trở đi',
                'valid_to.required' => 'Ngày kết thúc là bắt buộc',
                'valid_to.after' => 'Ngày kết thúc phải sau ngày bắt đầu',
                'applies_to.required' => 'Loại áp dụng là bắt buộc'
            ]);

            $validated['used_count'] = 0;
            $validated['is_deleted'] = 'active';

            $promotion = Promotion::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Tạo promotion thành công',
                'data' => $promotion
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tạo promotion',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Cập nhật promotion
    public function update(Request $request, $id)
    {
        try {
            $promotion = Promotion::find($id);

            if (!$promotion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Promotion không tồn tại'
                ], 404);
            }

            $validated = $request->validate([
                'code' => 'required|string|max:50|unique:promotions,code,' . $id . ',promotion_id',
                'discount' => 'required|numeric|min:0|max:100',
                'max_uses' => 'required|integer|min:1',
                'valid_from' => 'required|date',
                'valid_to' => 'required|date|after:valid_from',
                'applies_to' => ['required', Rule::in(['tour', 'combo', 'hotel', 'transport', 'all'])]
            ]);

            $promotion->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật promotion thành công',
                'data' => $promotion
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi cập nhật promotion',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Soft delete (ẩn/hiện promotion)
    public function softDelete($id)
    {
        try {
            $promotion = Promotion::find($id);

            if (!$promotion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Promotion không tồn tại'
                ], 404);
            }

            $newStatus = $promotion->is_deleted === 'active' ? 'inactive' : 'active';
            $promotion->update(['is_deleted' => $newStatus]);

            $action = $newStatus === 'inactive' ? 'ẩn' : 'hiện';

            return response()->json([
                'success' => true,
                'message' => "Đã {$action} promotion thành công",
                'data' => $promotion
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi cập nhật trạng thái promotion',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Xóa vĩnh viễn
    public function destroy($id)
    {
        try {
            $promotion = Promotion::find($id);

            if (!$promotion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Promotion không tồn tại'
                ], 404);
            }

            $promotion->delete();

            return response()->json([
                'success' => true,
                'message' => 'Xóa promotion thành công'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xóa promotion',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Danh sách promotions đã ẩn
    public function trashed(Request $request)
    {
        try {
            $promotions = Promotion::inactive()
                ->paginate($request->get('per_page', 10));

            return response()->json([
                'success' => true,
                'data' => $promotions
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy danh sách promotions đã ẩn',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Thống kê promotions
    public function statistics()
    {
        try {
            $stats = [
                'total' => Promotion::count(),
                'active' => Promotion::active()->count(),
                'inactive' => Promotion::inactive()->count(),
                'valid' => Promotion::active()
                    ->where('valid_from', '<=', now()->toDateString())
                    ->where('valid_to', '>=', now()->toDateString())
                    ->count(),
                'expired' => Promotion::active()
                    ->where('valid_to', '<', now()->toDateString())
                    ->count()
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy thống kê promotions',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}