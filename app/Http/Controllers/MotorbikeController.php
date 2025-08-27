<?php

namespace App\Http\Controllers;

use App\Models\Motorbike;
use App\Models\Album;
use App\Models\AlbumImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MotorbikeController extends Controller
{
    public function index()
    {
        $motorbikes = Motorbike::where('is_deleted', 'active')
            ->with(['album.images'])
            ->get()
            ->map(function ($motorbike) {
                if ($motorbike->album && $motorbike->album->images) {
                    $motorbike->album->images = $motorbike->album->images->map(function ($image) {
                        $image->image_url = $image->image_url ? config('app.url') . Storage::url($image->image_url) : null;
                        return $image;
                    });
                }
                return $motorbike;
            });

        return response()->json($motorbikes);
    }

    public function show($id)
    {
        $motorbike = Motorbike::with(['album.images'])->find($id);

        if (!$motorbike || $motorbike->is_deleted === 'inactive') {
            return response()->json(['message' => 'Không tìm thấy xe máy'], 404);
        }

        // Chuyển đổi image_url thành URL đầy đủ
        if ($motorbike->album && $motorbike->album->images) {
            $motorbike->album->images = $motorbike->album->images->map(function ($image) {
                $image->image_url = $image->image_url ? config('app.url') . Storage::url($image->image_url) : null;
                return $image;
            });
        }

        return response()->json($motorbike);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'bike_type' => 'required|string|max:100',
            'price_per_day' => 'required|numeric|min:0|max:99999999.99',
            'license_plate' => 'nullable|string|max:20',
            'description' => 'nullable|string',
            'rental_status' => 'required|in:available,rented,maintenance',
            'image' => 'nullable|image|max:2048',
        ]);

        $albumId = null;
        if ($request->hasFile('image')) {
            $album = Album::create(['title' => 'Album cho xe ' . $validated['bike_type']]);
            $albumId = $album->album_id;

            $imagePath = $request->file('image')->store("albums/{$albumId}", 'public');

            AlbumImage::create([
                'album_id' => $albumId,
                'image_url' => $imagePath,
                'caption' => 'Ảnh đại diện xe máy',
                'is_deleted' => 'active',
            ]);
        }

        $validated['album_id'] = $albumId;
        $validated['is_deleted'] = 'active';

        $motorbike = Motorbike::create($validated);

        // Tải lại dữ liệu với album.images và định dạng image_url
        $motorbike->load('album.images');
        if ($motorbike->album && $motorbike->album->images) {
            $motorbike->album->images = $motorbike->album->images->map(function ($image) {
                $image->image_url = $image->image_url ? config('app.url') . Storage::url($image->image_url) : null;
                return $image;
            });
        }

        return response()->json([
            'message' => 'Tạo xe máy thành công',
            'motorbike' => $motorbike,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $motorbike = Motorbike::find($id);
        if (!$motorbike || $motorbike->is_deleted === 'inactive') {
            return response()->json(['message' => 'Không tìm thấy xe máy'], 404);
        }

        $validated = $request->validate([
            'bike_type' => 'sometimes|string|max:100',
            'price_per_day' => 'sometimes|numeric|min:0|max:99999999.99',
            'license_plate' => 'nullable|string|max:20',
            'description' => 'nullable|string',
            'rental_status' => 'sometimes|in:available,rented,maintenance',
            'image' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $album = $motorbike->album_id
                ? Album::find($motorbike->album_id)
                : Album::create(['title' => 'Album cho xe ' . ($validated['bike_type'] ?? $motorbike->bike_type)]);

            if (!$motorbike->album_id) {
                $motorbike->album_id = $album->album_id;
            }

            // Xóa ảnh cũ (nếu có)
            $oldImages = AlbumImage::where('album_id', $album->album_id)->get();
            foreach ($oldImages as $oldImage) {
                Storage::disk('public')->delete($oldImage->image_url);
                $oldImage->delete();
            }

            $imagePath = $request->file('image')->store("albums/{$album->album_id}", 'public');

            AlbumImage::create([
                'album_id' => $album->album_id,
                'image_url' => $imagePath,
                'caption' => 'Cập nhật ảnh xe máy',
                'is_deleted' => 'active',
            ]);
        }

        $motorbike->fill($request->only([
            'bike_type',
            'price_per_day',
            'license_plate',
            'description',
            'rental_status',
        ]))->save();

        // Tải lại dữ liệu với album.images và định dạng image_url
        $motorbike->load('album.images');
        if ($motorbike->album && $motorbike->album->images) {
            $motorbike->album->images = $motorbike->album->images->map(function ($image) {
                $image->image_url = $image->image_url ? config('app.url') . Storage::url($image->image_url) : null;
                return $image;
            });
        }

        return response()->json([
            'message' => 'Cập nhật xe máy thành công',
            'motorbike' => $motorbike,
        ]);
    }

    public function softDelete($id)
    {
        $motorbike = Motorbike::find($id);

        if (!$motorbike) {
            return response()->json(['message' => 'Không tìm thấy xe máy'], 404);
        }

        $motorbike->is_deleted = $motorbike->is_deleted === 'active' ? 'inactive' : 'active';
        $motorbike->save();

        // Tải lại dữ liệu với album.images và định dạng image_url
        $motorbike->load('album.images');
        if ($motorbike->album && $motorbike->album->images) {
            $motorbike->album->images = $motorbike->album->images->map(function ($image) {
                $image->image_url = $image->image_url ? config('app.url') . Storage::url($image->image_url) : null;
                return $image;
            });
        }

        return response()->json(['message' => 'Chuyển trạng thái thành công', 'motorbike' => $motorbike]);
    }

    public function destroy($id)
    {
        $motorbike = Motorbike::find($id);

        if (!$motorbike) {
            return response()->json(['message' => 'Không tìm thấy xe máy'], 404);
        }

        // Xóa ảnh liên quan (nếu có)
        if ($motorbike->album_id) {
            $oldImages = AlbumImage::where('album_id', $motorbike->album_id)->get();
            foreach ($oldImages as $oldImage) {
                Storage::disk('public')->delete($oldImage->image_url);
                $oldImage->delete();
            }
            Album::where('album_id', $motorbike->album_id)->delete();
        }

        $motorbike->delete();

        return response()->json(['message' => 'Đã xóa xe máy vĩnh viễn']);
    }

    public function trashed()
    {
        $motorbikes = Motorbike::with(['album.images'])
            ->get()
            ->map(function ($motorbike) {
                if ($motorbike->album && $motorbike->album->images) {
                    $motorbike->album->images = $motorbike->album->images->map(function ($image) {
                        $image->image_url = $image->image_url ? config('app.url') . Storage::url($image->image_url) : null;
                        return $image;
                    });
                }
                return $motorbike;
            });

        return response()->json($motorbikes);
    }
}