<?php

namespace App\Http\Controllers;

use App\Models\TourCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class TourCategoryController extends Controller
{
    // Danh sách danh mục tour còn hoạt động
    public function index()
    {
        $categories = TourCategory::all();

        foreach ($categories as $cat) {
            $cat->thumbnail_url = $cat->thumbnail ? asset('storage/' . $cat->thumbnail) : null;
        }

        return response()->json($categories);
    }

    // Xem chi tiết
    public function show($id)
    {
        $category = TourCategory::where('is_deleted', 'active')->find($id);
        if (!$category) return response()->json(['message' => 'Không tìm thấy danh mục'], 404);

        $category->thumbnail_url = $category->thumbnail ? asset('storage/' . $category->thumbnail) : null;
        return response()->json($category);
    }

    // Thêm danh mục tour
    public function store(Request $request)
    {
        $request->validate([
            'category_name' => 'required|string|max:100',
            'thumbnail' => 'nullable|image'
        ]);

        $thumbnailPath = null;
        if ($request->hasFile('thumbnail')) {
            $thumbnailPath = $request->file('thumbnail')->store('thumbnails', 'public');
        }

        $category = TourCategory::create([
            'category_name' => $request->category_name,
            'thumbnail' => $thumbnailPath
        ]);

        $category->thumbnail_url = $thumbnailPath ? asset('storage/' . $thumbnailPath) : null;
        return response()->json(['message' => 'Thêm danh mục thành công', 'category' => $category]);
    }

    // Cập nhật danh mục tour
    public function update(Request $request, $id)
    {
        $category = TourCategory::where('is_deleted', 'active')->find($id);
        if (!$category) return response()->json(['message' => 'Không tìm thấy danh mục'], 404);

        $request->validate([
            'category_name' => 'sometimes|string|max:100',
            'thumbnail' => 'nullable|image|max:2048'
        ]);

        if ($request->hasFile('thumbnail')) {
            if ($category->thumbnail && Storage::disk('public')->exists($category->thumbnail)) {
                Storage::disk('public')->delete($category->thumbnail);
            }
            $category->thumbnail = $request->file('thumbnail')->store('thumbnails', 'public');
        }

        $category->fill($request->only(['category_name']));
        $category->save();

        $category->thumbnail_url = $category->thumbnail ? asset('storage/' . $category->thumbnail) : null;

        return response()->json(['message' => 'Cập nhật danh mục thành công', 'category' => $category]);
    }

    // Xóa mềm / khôi phục danh mục
    public function softDelete($id)
    {
        $category = TourCategory::find($id);
        if (!$category) return response()->json(['message' => 'Không tìm thấy danh mục'], 404);

        $category->is_deleted = $category->is_deleted === 'active' ? 'inactive' : 'active';
        $category->save();

        return response()->json(['message' => 'Cập nhật trạng thái danh mục thành công', 'category' => $category]);
    }

    // Xóa vĩnh viễn
    public function destroy($id)
    {
        $category = TourCategory::find($id);
        if (!$category) return response()->json(['message' => 'Không tìm thấy danh mục'], 404);

        if ($category->thumbnail) {
            Storage::disk('public')->delete($category->thumbnail);
        }

        $category->delete();

        return response()->json(['message' => 'Xóa danh mục vĩnh viễn thành công']);
    }
}
