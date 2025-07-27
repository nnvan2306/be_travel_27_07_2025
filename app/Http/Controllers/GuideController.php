<?php

namespace App\Http\Controllers;

use App\Models\Guide;
use App\Models\Album;
use App\Models\AlbumImage;
use Illuminate\Http\Request;
use App\Models\Rating;
use Illuminate\Support\Facades\Auth;

class GuideController extends Controller
{
    public function storeRating(Request $request, $id)
    {
        $guide = Guide::find($id);
        if (!$guide || $guide->is_deleted === 'inactive') {
            return response()->json(['message' => 'Không tìm thấy hướng dẫn viên'], 404);
        }

        $validated = $request->validate([
            'rating' => 'required|numeric|min:0|max:5',
            'comment' => 'nullable|string|max:500',
        ]);

        Rating::create([
            'rateable_id' => $id,
            'rateable_type' => Guide::class,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'],
            'user_id' => Auth::id(), // Giả sử người dùng đã đăng nhập
        ]);

        $guide = Guide::with('album.images')->find($id);
        $guide->album?->images->each(function ($image) {
            if ($image->image_url && !str_starts_with($image->image_url, 'http')) {
                $image->image_url = config('app.url') . '/storage/' . $image->image_url;
            }
        });

        $ratings = Rating::where('rateable_id', $id)
            ->where('rateable_type', Guide::class)
            ->avg('rating');
        $guide->average_rating = $ratings ? round($ratings, 1) : 0;
        $guide->save();

        return response()->json([
            'message' => 'Đánh giá thành công',
            'guide' => $guide,
        ]);
    }
    public function index()
    {
        $guides = Guide::with('album.images')->get();
        $guides->each(function ($guide) {
            if ($guide->album && $guide->album->images) {
                $guide->album->images->each(function ($image) {
                    if ($image->image_url && !str_starts_with($image->image_url, 'http')) {
                        $image->image_url = config('app.url') . '/storage/' . $image->image_url;
                    }
                });
            }
        });
        return response()->json($guides);
    }

    public function show($id)
    {
        $guide = Guide::with('album')->find($id);
        if (!$guide || $guide->is_deleted === 'inactive') {
            return response()->json(['message' => 'Không tìm thấy hướng dẫn viên'], 404);
        }

        return response()->json($guide);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'gender' => 'nullable|in:male,female',
            'language' => 'nullable|string|max:50',
            'experience_years' => 'nullable|integer|min:0',
            'price_per_day' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'is_available' => 'nullable|string|in:true,false',
            'image' => 'nullable|image|max:2048',
        ]);

        // Convert is_available from string to boolean
        if (isset($validated['is_available'])) {
            $validated['is_available'] = $validated['is_available'] === 'true';
        }

        $albumId = null;

        if ($request->hasFile('image')) {
            $album = Album::create(['title' => 'Album cho HDV ' . $request->name]);
            $albumId = $album->album_id;

            $imagePath = $request->file('image')->store("albums/{$albumId}", 'public');

            AlbumImage::create([
                'album_id' => $albumId,
                'image_url' => $imagePath,
                'caption' => 'Ảnh đại diện',
                'is_deleted' => 'active'
            ]);
        }

        $guide = Guide::create(array_merge($validated, [
            'album_id' => $albumId,
            'is_deleted' => 'active'
        ]));

        return response()->json(['message' => 'Tạo hướng dẫn viên thành công', 'guide' => $guide], 201);
    }

    public function update(Request $request, $id)
    {
        $guide = Guide::find($id);
        if (!$guide || $guide->is_deleted === 'inactive') {
            return response()->json(['message' => 'Không tìm thấy hướng dẫn viên'], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'gender' => 'nullable|in:male,female',
            'language' => 'nullable|string|max:50',
            'experience_years' => 'nullable|integer|min:0',
            'price_per_day' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'average_rating' => 'nullable|numeric|min:0|max:5',
            'is_available' => 'nullable|string|in:true,false',
            'image' => 'nullable|image|max:2048',
        ]);

        // Convert is_available from string to boolean
        if (isset($validated['is_available'])) {
            $validated['is_available'] = $validated['is_available'] === 'true';
        }

        if ($request->hasFile('image')) {
            if (!$guide->album_id) {
                $album = Album::create(['title' => 'Album cho HDV ' . $guide->name]);
                $guide->album_id = $album->album_id;
                $guide->save();
            }
            $imagePath = $request->file('image')->store("albums/{$guide->album_id}", 'public');
            AlbumImage::create([
                'album_id' => $guide->album_id,
                'image_url' => $imagePath,
                'caption' => 'Ảnh cập nhật',
                'is_deleted' => 'active'
            ]);
        }

        $guide->fill($validated)->save();

        return response()->json(['message' => 'Cập nhật hướng dẫn viên thành công', 'guide' => $guide]);
    }

    public function toggleDelete($id)
    {
        $guide = Guide::find($id);
        if (!$guide) {
            return response()->json(['message' => 'Không tìm thấy hướng dẫn viên'], 404);
        }

        $guide->is_deleted = $guide->is_deleted === 'active' ? 'inactive' : 'active';
        $guide->save();

        return response()->json(['message' => 'Chuyển trạng thái thành công', 'guide' => $guide]);
    }

    public function destroy($id)
    {
        $guide = Guide::find($id);
        if (!$guide) {
            return response()->json(['message' => 'Không tìm thấy hướng dẫn viên'], 404);
        }

        $guide->delete();

        return response()->json(['message' => 'Đã xóa hướng dẫn viên vĩnh viễn']);
    }

    public function trashed()
    {
        $guides = Guide::where('is_deleted', 'inactive')->with('album')->get();
        return response()->json($guides);
    }
}