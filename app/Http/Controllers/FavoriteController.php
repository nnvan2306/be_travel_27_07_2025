<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FavoriteController extends Controller
{
    /**
     * Liệt kê tất cả favorites
     */
    public function index(Request $request): JsonResponse
    {
        $query = Favorite::with(['user', 'tour'])->active();

        // Filter by user
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by tour
        if ($request->has('tour_id')) {
            $query->where('tour_id', $request->tour_id);
        }

        $favorites = $query->orderBy('created_at', 'desc')->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $favorites
        ]);
    }

    /**
     * Tạo mới favorite
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'tour_id' => 'required|integer|exists:tours,tour_id'
        ]);

        $userId = Auth::id();
        
        // Kiểm tra xem đã tồn tại favorite này chưa
        $existingFavorite = Favorite::where('user_id', $userId)
            ->where('tour_id', $request->tour_id)
            ->first();

        if ($existingFavorite) {
            if ($existingFavorite->is_deleted === 'inactive') {
                // Nếu đã bị xóa mềm, khôi phục lại
                $existingFavorite->update(['is_deleted' => 'active']);
                return response()->json([
                    'success' => true,
                    'message' => 'Đã thêm vào yêu thích',
                    'data' => $existingFavorite->load(['user', 'tour'])
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Tour này đã có trong danh sách yêu thích'
                ], 409);
            }
        }

        $favorite = Favorite::create([
            'user_id' => $userId,
            'tour_id' => $request->tour_id,
            'is_deleted' => 'active'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Đã thêm vào yêu thích',
            'data' => $favorite->load(['user', 'tour'])
        ], 201);
    }

    /**
     * Lấy chi tiết favorite theo ID
     */
    public function show(string $id): JsonResponse
    {
        $favorite = Favorite::with(['user', 'tour'])->find($id);

        if (!$favorite) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy favorite'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $favorite
        ]);
    }

    /**
     * Cập nhật favorite
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $favorite = Favorite::find($id);

        if (!$favorite) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy favorite'
            ], 404);
        }

        $request->validate([
            'tour_id' => 'sometimes|integer|exists:tours,tour_id',
            'is_deleted' => 'sometimes|in:active,inactive'
        ]);

        $favorite->update($request->only(['tour_id', 'is_deleted']));

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật thành công',
            'data' => $favorite->load(['user', 'tour'])
        ]);
    }

    /**
     * Xóa mềm favorite
     */
    public function softDelete(string $id): JsonResponse
    {
        $favorite = Favorite::find($id);

        if (!$favorite) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy favorite'
            ], 404);
        }

        $favorite->update(['is_deleted' => 'inactive']);

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa khỏi danh sách yêu thích'
        ]);
    }

    /**
     * Xóa vĩnh viễn favorite
     */
    public function destroy(string $id): JsonResponse
    {
        $favorite = Favorite::find($id);

        if (!$favorite) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy favorite'
            ], 404);
        }

        $favorite->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa vĩnh viễn favorite'
        ]);
    }

    /**
     * Liệt kê favorites đã xóa
     */
    public function trashed(): JsonResponse
    {
        $favorites = Favorite::with(['user', 'tour'])
            ->inactive()
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $favorites
        ]);
    }

    /**
     * Khôi phục favorite đã xóa
     */
    public function restore(string $id): JsonResponse
    {
        $favorite = Favorite::where('favorite_id', $id)
            ->where('is_deleted', 'inactive')
            ->first();

        if (!$favorite) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy favorite đã xóa'
            ], 404);
        }

        $favorite->update(['is_deleted' => 'active']);

        return response()->json([
            'success' => true,
            'message' => 'Khôi phục thành công',
            'data' => $favorite->load(['user', 'tour'])
        ]);
    }

    /**
     * Lấy danh sách yêu thích của user hiện tại
     */
    public function myFavorites(): JsonResponse
    {
        $favorites = Favorite::with(['tour'])
            ->where('user_id', Auth::id())
            ->active()
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $favorites
        ]);
    }

    /**
     * Thống kê favorites
     */
    public function statistics(): JsonResponse
    {
        $stats = [
            'total_favorites' => Favorite::active()->count(),
            'total_users_with_favorites' => Favorite::active()->distinct('user_id')->count(),
            'total_tours_favorited' => Favorite::active()->distinct('tour_id')->count(),
            'inactive_favorites' => Favorite::inactive()->count(),
            'most_favorited_tours' => Favorite::select('tour_id', DB::raw('COUNT(*) as count'))
                ->with('tour')
                ->active()
                ->groupBy('tour_id')
                ->orderBy('count', 'desc')
                ->limit(5)
                ->get()
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Toggle trạng thái yêu thích theo tour_id
     */
    public function toggle(Request $request): JsonResponse
    {
        $request->validate([
            'tour_id' => 'required|integer|exists:tours,tour_id'
        ]);

        $userId = Auth::id();

        $favorite = Favorite::where('user_id', $userId)
            ->where('tour_id', $request->tour_id)
            ->first();

        if ($favorite) {
            if ($favorite->is_deleted === 'active') {
                $favorite->update(['is_deleted' => 'inactive']);
                return response()->json([
                    'success' => true,
                    'message' => 'Đã xóa khỏi yêu thích',
                    'is_favorited' => false,
                    'data' => $favorite->fresh()
                ]);
            }

            $favorite->update(['is_deleted' => 'active']);
            return response()->json([
                'success' => true,
                'message' => 'Đã thêm vào yêu thích',
                'is_favorited' => true,
                'data' => $favorite->fresh()->load(['tour'])
            ]);
        }

        $favorite = Favorite::create([
            'user_id' => $userId,
            'tour_id' => $request->tour_id,
            'is_deleted' => 'active'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Đã thêm vào yêu thích',
            'is_favorited' => true,
            'data' => $favorite->load(['tour'])
        ], 201);
    }

    /**
     * Lấy danh sách id tour đã yêu thích (đơn giản, không phân trang)
     */
    public function ids(): JsonResponse
    {
        $userId = Auth::id();
        $favorites = Favorite::where('user_id', $userId)
            ->where('is_deleted', 'active')
            ->get(['favorite_id', 'tour_id']);

        return response()->json([
            'success' => true,
            'data' => [
                'ids' => $favorites->pluck('tour_id')->map(fn($v) => (int) $v)->values(),
                'map' => $favorites->mapWithKeys(fn($f) => [ (int) $f->tour_id => (int) $f->favorite_id ])
            ]
        ]);
    }
}