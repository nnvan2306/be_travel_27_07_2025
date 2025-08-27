<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AlbumImage;
use App\Models\Album;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AlbumImageController extends Controller
{
    public function allImages()
    {
        $images = AlbumImage::where('is_deleted', 'active')
            ->get()
            ->map(function ($image) {
                $image->image_url_full = asset('storage/' . $image->image_url);
                return $image;
            });

        return response()->json(['message' => 'Tất cả hình ảnh', 'images' => $images]);
    }

    public function index($albumId)
    {
        $images = AlbumImage::where('album_id', $albumId)->where('is_deleted', 'active')->get()->map(function ($img) {
            $img->image_url_full = asset('storage/' . $img->image_url);
            return $img;
        });

        return response()->json($images);
    }

    public function store(Request $request, $albumId)
    {
        $request->validate([
            'images' => 'required|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:5120'
        ]);

        $uploaded = [];
        foreach ($request->file('images') as $index => $file) {
            $path = $file->store("albums/{$albumId}", 'public');
            $image = AlbumImage::create([
                'album_id' => $albumId,
                'image_url' => $path,
                'caption' => $request->captions[$index] ?? null,
                'is_deleted' => 'active'
            ]);
            $image->image_url_full = asset('storage/' . $image->image_url);
            $uploaded[] = $image;
        }

        return response()->json(['message' => 'Thêm ảnh thành công', 'images' => $uploaded]);
    }

    public function softDelete($albumId, $imageId)
    {
        $image = AlbumImage::where('album_id', $albumId)->where('image_id', $imageId)->first();
        if (!$image)
            return response()->json(['message' => 'Không tìm thấy ảnh'], 404);

        $image->is_deleted = $image->is_deleted === 'active' ? 'inactive' : 'active';
        $image->save();

        return response()->json(['message' => 'Cập nhật trạng thái ảnh thành công', 'image' => $image]);
    }

    public function destroy($albumId, $imageId)
    {
        $image = AlbumImage::where('image_id', $imageId)->first();
        if (!$image)
            return response()->json(['message' => 'Không tìm thấy ảnh'], 404);

        // Xóa file thực tế trước
        if (Storage::disk('public')->exists($image->image_url)) {
            Storage::disk('public')->delete($image->image_url);
        }
        
        // Sử dụng soft delete thay vì hard delete
        $image->is_deleted = 'inactive';
        $image->save();

        return response()->json(['message' => 'Xóa ảnh thành công']);
    }

    public function forceDelete($imageId)
    {
        $image = AlbumImage::where('image_id', $imageId)->first();
        if (!$image)
            return response()->json(['message' => 'Không tìm thấy ảnh'], 404);

        // Xóa file thực tế trước
        if (Storage::disk('public')->exists($image->image_url)) {
            Storage::disk('public')->delete($image->image_url);
        }
        
        // Xóa hoàn toàn khỏi database
        $image->delete();

        return response()->json(['message' => 'Xóa ảnh hoàn toàn thành công']);
    }

    public function trashed($albumId)
    {
        $images = AlbumImage::where('album_id', $albumId)->where('is_deleted', 'inactive')->get()->map(function ($img) {
            $img->image_url_full = asset('storage/' . $img->image_url);
            return $img;
        });
        return response()->json($images);
    }

    public function show($albumId, $imageId)
    {
        $image = AlbumImage::where('album_id', $albumId)
            ->where('image_id', $imageId)
            ->where('is_deleted', 'active')
            ->first();
            
        if (!$image) {
            return response()->json(['message' => 'Không tìm thấy ảnh'], 404);
        }

        $image->image_url_full = asset('storage/' . $image->image_url);
        return response()->json($image);
    }

    public function update(Request $request, $albumId, $imageId)
    {
        $image = AlbumImage::where('album_id', $albumId)
            ->where('image_id', $imageId)
            ->where('is_deleted', 'active')
            ->first();
            
        if (!$image) {
            return response()->json(['message' => 'Không tìm thấy ảnh'], 404);
        }

        $request->validate([
            'caption' => 'nullable|string|max:255'
        ]);

        $image->caption = $request->caption;
        $image->save();

        $image->image_url_full = asset('storage/' . $image->image_url);
        return response()->json(['message' => 'Cập nhật ảnh thành công', 'image' => $image]);
    }

    public function statistics($albumId)
    {
        $totalImages = AlbumImage::where('album_id', $albumId)->count();
        $activeImages = AlbumImage::where('album_id', $albumId)->where('is_deleted', 'active')->count();
        $inactiveImages = AlbumImage::where('album_id', $albumId)->where('is_deleted', 'inactive')->count();

        return response()->json([
            'total_images' => $totalImages,
            'active_images' => $activeImages,
            'inactive_images' => $inactiveImages
        ]);
    }
}