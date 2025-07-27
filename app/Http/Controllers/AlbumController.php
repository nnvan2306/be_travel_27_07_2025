<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\AlbumImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AlbumController extends Controller
{
    public function index()
    {
            $albums = Album::with('images') // Lấy tất cả ảnh của mỗi album
            ->get()
            ->map(function ($album) {
                // Ảnh đầu tiên (dù active hay inactive)
                $firstImage = $album->images->first();
                $album->image_url_full = $firstImage ? asset('storage/' . $firstImage->image_url) : null;

                // Đếm tổng ảnh
                $album->images_count = $album->images->count();

                return $album; // Trả về cả album và danh sách ảnh
            });

        return response()->json($albums);
    }

    public function show($id)
    {
        $album = Album::with(['images' => fn($query) => $query->where('is_deleted', 'active')])->find($id);

        if (!$album) {
            return response()->json(['message' => 'Không tìm thấy album'], 404);
        }

        $album->images->map(function ($image) {
            $image->image_url_full = asset('storage/' . $image->image_url);
            return $image;
        });

        return response()->json($album);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255|unique:albums,title,NULL,album_id,is_deleted,active'
        ]);

        $album = Album::create([
            'title' => $request->title,
            'is_deleted' => 'active'
        ]);

        return response()->json(['message' => 'Tạo album thành công', 'album' => $album], 201);
    }

    public function update(Request $request, $id)
    {
        $album = Album::find($id);
        if (!$album) return response()->json(['message' => 'Không tìm thấy album'], 404);

        $request->validate([
            'title' => 'required|string|max:255|unique:albums,title,' . $id . ',album_id,is_deleted,active'
        ]);

        $album->update(['title' => $request->title]);
        return response()->json(['message' => 'Cập nhật album thành công', 'album' => $album]);
    }

    public function softDelete($id)
    {
        $album = Album::find($id);
        if (!$album) return response()->json(['message' => 'Không tìm thấy album'], 404);

        $album->is_deleted = $album->is_deleted === 'active' ? 'inactive' : 'active';
        $album->save();

        return response()->json(['message' => 'Cập nhật trạng thái album thành công', 'album' => $album]);
    }

    public function destroy($id)
    {
        $album = Album::find($id);
        if (!$album) return response()->json(['message' => 'Không tìm thấy album'], 404);

        foreach ($album->allImages as $img) {
            if (Storage::disk('public')->exists($img->image_url)) {
                Storage::disk('public')->delete($img->image_url);
            }
            $img->delete();
        }

        $album->delete();
        return response()->json(['message' => 'Xóa album thành công']);
    }

    public function trashed()
    {
        $albums = Album::where('is_deleted', 'inactive')
            ->with(['images' => fn($q) => $q->where('is_deleted', 'active')->take(1)])
            ->get()
            ->map(function ($album) {
                $firstImage = $album->images->first();
                $album->image_url_full = $firstImage ? asset('storage/' . $firstImage->image_url) : null;
                $album->images_count = $album->images()->where('is_deleted', 'active')->count();
                unset($album->images);
                return $album;
            });
        return response()->json($albums);
    }
}
