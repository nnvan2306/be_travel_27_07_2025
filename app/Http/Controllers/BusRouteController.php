<?php

namespace App\Http\Controllers;

use App\Models\BusRoute;
use App\Models\Album;
use App\Models\AlbumImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BusRouteController extends Controller
{
    /**
     * Danh sách tất cả tuyến xe (kể cả đã bị ẩn)
     */
    public function index()
    {
        $busRoutes = BusRoute::with('album.images')->active()->get();

        $busRoutes->each(function ($route) {
            if ($route->album && $route->album->images) {
                $route->album->images->each(function ($image) {
                    if ($image->image_url && !str_starts_with($image->image_url, 'http')) {
                        $image->image_url = config('app.url') . '/storage/' . $image->image_url;
                    }
                });
            }
        });

        return response()->json($busRoutes);
    }

    /**
     * Hiển thị tuyến xe theo ID
     */
    public function show($id)
    {
        $route = BusRoute::with('album.images')->find($id);
        if (!$route || $route->is_deleted === 'inactive') {
            return response()->json(['message' => 'Không tìm thấy tuyến xe'], 404);
        }

        return response()->json($route);
    }

    /**
     * Tạo tuyến xe mới
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'route_name' => 'required|string|max:255',
            'vehicle_type' => 'required|string|max:100',
            'price' => 'required|numeric|min:0',
            'seats' => 'required|integer|min:1',
            'license_plate' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
        ]);

        $albumId = null;

        if ($request->hasFile('image')) {
            $album = Album::create(['title' => 'Album cho tuyến ' . $validated['route_name']]);
            $albumId = $album->album_id;

            $imagePath = $request->file('image')->store("albums/{$albumId}", 'public');

            AlbumImage::create([
                'album_id' => $albumId,
                'image_url' => $imagePath,
                'caption' => 'Ảnh đại diện tuyến xe',
                'is_deleted' => 'active',
            ]);
        }

        $validated['album_id'] = $albumId;
        $validated['is_deleted'] = 'active';

        $route = BusRoute::create($validated);

        return response()->json([
            'message' => 'Tạo tuyến xe thành công',
            'route' => $route->load('album.images'),
        ], 201);
    }

    /**
     * Cập nhật tuyến xe
     */
    public function update(Request $request, $id)
    {
        $route = BusRoute::find($id);
        if (!$route || $route->is_deleted === 'inactive') {
            return response()->json(['message' => 'Không tìm thấy tuyến xe'], 404);
        }

        $validated = $request->validate([
            'route_name' => 'sometimes|string|max:255',
            'vehicle_type' => 'sometimes|string|max:100',
            'price' => 'required|numeric|min:0|max:99999999.99',
            'seats' => 'sometimes|integer|min:1',
            'license_plate' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $album = $route->album_id
                ? Album::find($route->album_id)
                : Album::create(['title' => 'Album cho tuyến ' . ($validated['route_name'] ?? $route->route_name)]);

            if (!$route->album_id) {
                $route->album_id = $album->album_id;
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
                'caption' => 'Cập nhật ảnh tuyến xe',
                'is_deleted' => 'active',
            ]);
        }

        $route->fill($validated)->save();

        return response()->json([
            'message' => 'Cập nhật tuyến xe thành công',
            'route' => $route->load('album.images'),
        ]);
    }

    /**
     * Ẩn / Hiện tuyến xe (Soft Delete)
     */
    public function softDelete($id)
    {
        $route = BusRoute::find($id);
        if (!$route) {
            return response()->json(['message' => 'Không tìm thấy tuyến xe'], 404);
        }

        $route->is_deleted = $route->is_deleted === 'active' ? 'inactive' : 'active';
        $route->save();

        return response()->json([
            'message' => $route->is_deleted === 'inactive' ? 'Tuyến xe đã bị ẩn' : 'Tuyến xe đã được khôi phục',
            'route' => $route->load('album.images'),
        ]);
    }

    /**
     * Xoá vĩnh viễn tuyến xe
     */
    public function destroy($id)
    {
        $route = BusRoute::find($id);
        if (!$route) {
            return response()->json(['message' => 'Không tìm thấy tuyến xe'], 404);
        }

        // Xóa ảnh và album liên quan (nếu có)
        if ($route->album_id) {
            $oldImages = AlbumImage::where('album_id', $route->album_id)->get();
            foreach ($oldImages as $oldImage) {
                Storage::disk('public')->delete($oldImage->image_url);
                $oldImage->delete();
            }
            Album::where('album_id', $route->album_id)->delete();
        }

        $route->delete();

        return response()->json(['message' => 'Đã xoá tuyến xe vĩnh viễn']);
    }

    /**
     * Danh sách tuyến xe đã bị ẩn (inactive)
     */
    public function trashed()
    {
        $trashed = BusRoute::with('album.images')
            ->where('is_deleted', 'inactive')
            ->get();

        return response()->json($trashed);
    }
}