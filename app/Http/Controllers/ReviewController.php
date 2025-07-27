<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Tour;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReviewController extends Controller
{
    // Liệt kê tất cả reviews
    public function index(Request $request)
    {
        try {
            $query = Review::active()->with(['user', 'tour']);

            // Filter theo user_id
            if ($request->has('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            // Filter theo tour_id
            if ($request->has('tour_id')) {
                $query->where('tour_id', $request->tour_id);
            }

            // Filter theo rating
            if ($request->has('rating')) {
                $query->where('rating', $request->rating);
            }

            // Search trong comment
            if ($request->has('search')) {
                $query->where('comment', 'like', '%' . $request->search . '%');
            }

            // Sắp xếp
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Phân trang
            $perPage = $request->get('per_page', 15);
            $reviews = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $reviews
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy danh sách reviews',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Tạo mới review
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|integer|exists:users,id',
                'tour_id' => 'required|integer|exists:tours,tour_id',
                'rating' => 'required|integer|min:1|max:5',
                'comment' => 'nullable|string|max:1000'
            ], [
                'user_id.required' => 'ID người dùng là bắt buộc',
                'user_id.exists' => 'Người dùng không tồn tại',
                'tour_id.required' => 'ID tour là bắt buộc',
                'tour_id.exists' => 'Tour không tồn tại',
                'rating.required' => 'Điểm đánh giá là bắt buộc',
                'rating.min' => 'Điểm đánh giá tối thiểu là 1',
                'rating.max' => 'Điểm đánh giá tối đa là 5',
                'comment.max' => 'Bình luận không được vượt quá 1000 ký tự'
            ]);

            // Bỏ phần check existing review nếu muốn cho phép đánh giá nhiều lần
            // Hoặc giữ lại nếu muốn 1 user chỉ đánh giá 1 lần cho 1 tour

            $validated['created_at'] = now();
            $validated['is_deleted'] = 'active';

            $review = Review::create($validated);
            $review->load(['user', 'tour']);

            return response()->json([
                'success' => true,
                'message' => 'Tạo review thành công',
                'data' => $review
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
                'message' => 'Lỗi khi tạo review',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Lấy chi tiết review
    public function show($id)
    {
        try {
            $review = Review::active()->with(['user', 'tour'])->find($id);

            if (!$review) {
                return response()->json([
                    'success' => false,
                    'message' => 'Review không tồn tại'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $review
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy chi tiết review',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Cập nhật review
    public function update(Request $request, $id)
    {
        try {
            $review = Review::active()->find($id);

            if (!$review) {
                return response()->json([
                    'success' => false,
                    'message' => 'Review không tồn tại'
                ], 404);
            }

            $validated = $request->validate([
                'rating' => 'required|integer|min:1|max:5',
                'comment' => 'nullable|string|max:1000'
            ], [
                'rating.required' => 'Điểm đánh giá là bắt buộc',
                'rating.min' => 'Điểm đánh giá tối thiểu là 1',
                'rating.max' => 'Điểm đánh giá tối đa là 5',
                'comment.max' => 'Bình luận không được vượt quá 1000 ký tự'
            ]);

            $review->update($validated);
            $review->load(['user', 'tour']);

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật review thành công',
                'data' => $review
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
                'message' => 'Lỗi khi cập nhật review',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Xóa mềm review
    public function softDelete($id)
    {
        try {
            $review = Review::active()->find($id);

            if (!$review) {
                return response()->json([
                    'success' => false,
                    'message' => 'Review không tồn tại'
                ], 404);
            }

            $review->update(['is_deleted' => 'inactive']);

            return response()->json([
                'success' => true,
                'message' => 'Ẩn review thành công'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi ẩn review',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Xóa vĩnh viễn review
    public function destroy($id)
    {
        try {
            $review = Review::find($id);

            if (!$review) {
                return response()->json([
                    'success' => false,
                    'message' => 'Review không tồn tại'
                ], 404);
            }

            $review->delete();

            return response()->json([
                'success' => true,
                'message' => 'Xóa review thành công'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xóa review',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Danh sách reviews đã ẩn
    public function trashed(Request $request)
    {
        try {
            $query = Review::inactive()->with(['user', 'tour']);

            $perPage = $request->get('per_page', 15);
            $reviews = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $reviews
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy danh sách reviews đã ẩn',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Khôi phục review đã ẩn
    public function restore($id)
    {
        try {
            $review = Review::inactive()->find($id);

            if (!$review) {
                return response()->json([
                    'success' => false,
                    'message' => 'Review không tồn tại hoặc chưa bị ẩn'
                ], 404);
            }

            $review->update(['is_deleted' => 'active']);

            return response()->json([
                'success' => true,
                'message' => 'Khôi phục review thành công'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi khôi phục review',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Thống kê reviews
    public function statistics()
    {
        try {
            $stats = [
                'total' => Review::count(),
                'active' => Review::active()->count(),
                'inactive' => Review::inactive()->count(),
                'average_rating' => round(Review::active()->avg('rating'), 2),
                'rating_distribution' => [
                    '5_stars' => Review::active()->where('rating', 5)->count(),
                    '4_stars' => Review::active()->where('rating', 4)->count(),
                    '3_stars' => Review::active()->where('rating', 3)->count(),
                    '2_stars' => Review::active()->where('rating', 2)->count(),
                    '1_star' => Review::active()->where('rating', 1)->count(),
                ],
                'total_today' => Review::active()->whereDate('created_at', today())->count(),
                'total_this_month' => Review::active()->whereMonth('created_at', now()->month)->count()
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy thống kê reviews',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Lấy reviews theo tour
    public function getByTour($tourId, Request $request)
    {
        try {
            $tour = Tour::find($tourId);
            if (!$tour) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tour không tồn tại'
                ], 404);
            }

            $query = Review::active()->where('tour_id', $tourId)->with(['user']);

            $perPage = $request->get('per_page', 10);
            $reviews = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $reviews,
                'tour_info' => $tour
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy reviews của tour',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}