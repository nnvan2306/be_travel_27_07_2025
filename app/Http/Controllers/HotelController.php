<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use App\Models\Album;
use App\Models\AlbumImage;
use App\Models\Rating;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class HotelController extends Controller
{
    public function index()
    {
        $hotels = Hotel::where('is_deleted', 'active')
            ->with('album.images')
            ->get();

        $hotels->each(function ($hotel) {
            if ($hotel->album && $hotel->album->images) {
                $hotel->album->images->each(function ($image) {
                    if ($image->image_url && !str_starts_with($image->image_url, 'http')) {
                        $image->image_url = config('app.url') . '/storage/' . $image->image_url;
                    }
                });
            }
        });

        return response()->json($hotels);
    }

    public function show($id)
    {
        $hotel = Hotel::where('is_deleted', 'active')
            ->with('album.images')
            ->find($id);

        if (!$hotel) {
            return response()->json(['message' => 'Không tìm thấy khách sạn'], 404);
        }

        if ($hotel->album && $hotel->album->images) {
            $hotel->album->images->each(function ($image) {
                if ($image->image_url && !str_starts_with($image->image_url, 'http')) {
                    $image->image_url = config('app.url') . '/storage/' . $image->image_url;
                }
            });
        }

        return response()->json($hotel);
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'location' => 'nullable|string|max:255',
                'room_type' => 'nullable|string|max:100',
                'price' => 'required|numeric|min:0',
                'description' => 'nullable|string',
                'image' => 'nullable|image|max:2048',
                'contact_phone' => 'nullable|string|max:50',
                'contact_email' => 'nullable|email|max:100',
                'max_guests' => 'nullable|integer|min:1',
                'facilities' => 'nullable|string',
                'bed_type' => 'nullable|string|max:100',
                'is_available' => 'nullable|boolean',
            ]);

            $album = Album::create(['title' => 'Album khách sạn ' . $request->name]);
            $imagePath = null;

            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store("albums/{$album->album_id}", 'public');
                AlbumImage::create([
                    'album_id' => $album->album_id,
                    'image_url' => $imagePath,
                    'caption' => 'Ảnh đại diện',
                    'is_deleted' => 'active',
                ]);
            }

            $hotel = Hotel::create([
                'name' => $validated['name'],
                'location' => $validated['location'],
                'room_type' => $validated['room_type'],
                'price' => $validated['price'],
                'description' => $validated['description'],
                'image' => $imagePath ? config('app.url') . '/storage/' . $imagePath : null,
                'album_id' => $album->album_id,
                'contact_phone' => $validated['contact_phone'],
                'contact_email' => $validated['contact_email'],
                'max_guests' => $validated['max_guests'],
                'facilities' => $validated['facilities'],
                'bed_type' => $validated['bed_type'],
                'is_available' => $validated['is_available'] ?? true,
                'average_rating' => 0,
                'is_deleted' => 'active',
            ]);

            return response()->json(['message' => 'Tạo khách sạn thành công', 'hotel' => $hotel], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function update(Request $request, $id)
    {
        $hotel = Hotel::find($id);
        if (!$hotel || $hotel->is_deleted === 'inactive') {
            return response()->json(['message' => 'Không tìm thấy khách sạn'], 404);
        }

        try {
            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'location' => 'sometimes|string|max:255',
                'room_type' => 'nullable|string|max:100',
                'price' => 'sometimes|numeric|min:0',
                'description' => 'nullable|string',
                'image' => 'nullable|image|max:2048',
                'contact_phone' => 'nullable|string|max:50',
                'contact_email' => 'nullable|email|max:100',
                'max_guests' => 'nullable|integer|min:1',
                'facilities' => 'nullable|string',
                'bed_type' => 'nullable|string|max:100',
                'is_available' => 'sometimes|boolean',
            ]);

            if ($request->hasFile('image')) {
                if (!$hotel->album_id) {
                    $album = Album::create(['title' => 'Album khách sạn ' . ($request->name ?? $hotel->name)]);
                    $hotel->album_id = $album->album_id;
                    $hotel->save();
                }
                $imagePath = $request->file('image')->store("albums/{$hotel->album_id}", 'public');
                $validated['image'] = config('app.url') . '/storage/' . $imagePath;

                AlbumImage::create([
                    'album_id' => $hotel->album_id,
                    'image_url' => $imagePath,
                    'caption' => 'Ảnh cập nhật',
                    'is_deleted' => 'active',
                ]);
            }

            $hotel->fill($validated)->save();

            $hotel->refresh()->load('album.images');
            if ($hotel->album && $hotel->album->images) {
                $hotel->album->images->each(function ($image) {
                    if ($image->image_url && !str_starts_with($image->image_url, 'http')) {
                        $image->image_url = config('app.url') . '/storage/' . $image->image_url;
                    }
                });
            }

            return response()->json(['message' => 'Cập nhật khách sạn thành công', 'hotel' => $hotel]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function storeRating(Request $request, $id)
    {
        $hotel = Hotel::find($id);
        if (!$hotel || $hotel->is_deleted === 'inactive') {
            return response()->json(['message' => 'Không tìm thấy khách sạn'], 404);
        }

        try {
            $validated = $request->validate([
                'rating' => 'required|numeric|min:0|max:5',
                'comment' => 'nullable|string|max:500',
            ]);

            if (!Auth::check()) {
                return response()->json(['message' => 'Bạn cần đăng nhập để đánh giá'], 401);
            }

            Rating::create([
                'rateable_id' => $id,
                'rateable_type' => Hotel::class,
                'rating' => $validated['rating'],
                'comment' => $validated['comment'],
                'user_id' => Auth::id(),
            ]);

            // Tính toán average_rating
            $averageRating = Rating::where('rateable_id', $id)
                ->where('rateable_type', Hotel::class)
                ->avg('rating');
            $hotel->average_rating = $averageRating ? round($averageRating, 1) : 0;
            $hotel->save();

            $hotel->refresh()->load('album.images');
            if ($hotel->album && $hotel->album->images) {
                $hotel->album->images->each(function ($image) {
                    if ($image->image_url && !str_starts_with($image->image_url, 'http')) {
                        $image->image_url = config('app.url') . '/storage/' . $image->image_url;
                    }
                });
            }

            return response()->json([
                'message' => 'Đánh giá thành công',
                'hotel' => $hotel,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function softDelete($id)
    {
        $hotel = Hotel::find($id);
        if (!$hotel) {
            return response()->json(['message' => 'Không tìm thấy khách sạn'], 404);
        }

        $hotel->is_deleted = $hotel->is_deleted === 'active' ? 'inactive' : 'active';
        $hotel->save();

        return response()->json(['message' => 'Đã chuyển trạng thái khách sạn thành công', 'hotel' => $hotel]);
    }

    public function destroy($id)
    {
        $hotel = Hotel::find($id);
        if (!$hotel) {
            return response()->json(['message' => 'Không tìm thấy khách sạn'], 404);
        }

        $hotel->delete();

        return response()->json(['message' => 'Đã xóa khách sạn vĩnh viễn']);
    }

    public function trashed()
    {
        $hotels = Hotel::with('album.images')->get();

        $hotels->each(function ($hotel) {
            if ($hotel->album && $hotel->album->images) {
                $hotel->album->images->each(function ($image) {
                    if ($image->image_url && !str_starts_with($image->image_url, 'http')) {
                        $image->image_url = config('app.url') . '/storage/' . $image->image_url;
                    }
                });
            }
        });

        return response()->json($hotels);
    }
}