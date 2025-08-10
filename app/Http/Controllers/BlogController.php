<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class BlogController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Blog::query();

        // Tìm kiếm
        if ($request->has('search')) {
            $query->search($request->search);
        }

        // Lọc theo status
        if ($request->has('status')) {
            if ($request->status === 'published') {
                $query->published();
            } elseif ($request->status === 'draft') {
                $query->draft();
            }
        }

        // Sắp xếp
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Phân trang
        $perPage = $request->get('per_page', 10);
        $blogs = $query->paginate($perPage);

        return response()->json([
            'message' => 'Lấy danh sách blog thành công',
            'data' => $blogs
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'markdown' => 'required|string',
            'location' => 'nullable|string|max:255',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'nullable|in:published,draft'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $blogData = $request->only(['title', 'description', 'markdown', 'location', 'status']);
            $blogData['status'] = $blogData['status'] ?? 'published';

            // Xử lý thumbnail
            if ($request->hasFile('thumbnail')) {
                try {
                    $originalName = $request->file('thumbnail')->getClientOriginalName();
                    $sanitizedName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
                    $fileName = time() . '_' . $sanitizedName;
                    $thumbnailPath = $request->file('thumbnail')->storeAs('blogs', $fileName, 'public');
                    $blogData['thumbnail'] = $thumbnailPath;
                } catch (\Exception $e) {
                    return response()->json(['message' => 'Lỗi khi upload thumbnail: ' . $e->getMessage()], 500);
                }
            }

            // Tạo slug
            $slug = Str::slug($request->title);
            if (Blog::where('slug', $slug)->exists()) {
                $slug .= '-' . uniqid();
            }
            $blogData['slug'] = $slug;

            $blog = Blog::create($blogData);

            return response()->json([
                'message' => 'Tạo blog thành công',
                'data' => $blog
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Lỗi khi tạo blog: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $blog = Blog::find($id);

        if (!$blog) {
            return response()->json(['message' => 'Blog không tồn tại'], 404);
        }

        // Tăng số lượt xem
        $blog->incrementViewCount();

        return response()->json([
            'message' => 'Lấy chi tiết blog thành công',
            'data' => $blog
        ]);
    }

    /**
     * Show blog by slug
     */
    public function showBySlug($slug)
    {
        $blog = Blog::where('slug', $slug)->first();

        if (!$blog) {
            return response()->json(['message' => 'Blog không tồn tại'], 404);
        }

        // Tăng số lượt xem
        $blog->incrementViewCount();

        return response()->json([
            'message' => 'Lấy chi tiết blog thành công',
            'data' => $blog
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $blog = Blog::find($id);

        if (!$blog) {
            return response()->json(['message' => 'Blog không tồn tại'], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'markdown' => 'sometimes|required|string',
            'location' => 'nullable|string|max:255',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'nullable|in:published,draft'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $blogData = $request->only(['title', 'description', 'markdown', 'location', 'status']);

            // Xử lý thumbnail
            if ($request->hasFile('thumbnail')) {
                try {
                    // Xóa thumbnail cũ
                    if ($blog->thumbnail && Storage::disk('public')->exists($blog->thumbnail)) {
                        Storage::disk('public')->delete($blog->thumbnail);
                    }

                    $originalName = $request->file('thumbnail')->getClientOriginalName();
                    $sanitizedName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
                    $fileName = time() . '_' . $sanitizedName;
                    $thumbnailPath = $request->file('thumbnail')->storeAs('blogs', $fileName, 'public');
                    $blogData['thumbnail'] = $thumbnailPath;
                } catch (\Exception $e) {
                    return response()->json(['message' => 'Lỗi khi upload thumbnail: ' . $e->getMessage()], 500);
                }
            }

            // Cập nhật slug nếu title thay đổi
            if ($request->has('title') && $request->title !== $blog->title) {
                $slug = Str::slug($request->title);
                if (Blog::where('slug', $slug)->where('id', '!=', $blog->id)->exists()) {
                    $slug .= '-' . uniqid();
                }
                $blogData['slug'] = $slug;
            }

            $blog->update($blogData);

            return response()->json([
                'message' => 'Cập nhật blog thành công',
                'data' => $blog
            ]);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Lỗi khi cập nhật blog: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $blog = Blog::find($id);

        if (!$blog) {
            return response()->json(['message' => 'Blog không tồn tại'], 404);
        }

        try {
            // Xóa thumbnail
            if ($blog->thumbnail && Storage::disk('public')->exists($blog->thumbnail)) {
                Storage::disk('public')->delete($blog->thumbnail);
            }

            $blog->delete();

            return response()->json(['message' => 'Xóa blog thành công']);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Lỗi khi xóa blog: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get published blogs only
     */
    public function published()
    {
        $blogs = Blog::published()->orderBy('created_at', 'desc')->paginate(10);

        return response()->json([
            'message' => 'Lấy danh sách blog đã xuất bản thành công',
            'data' => $blogs
        ]);
    }

    /**
     * Get popular blogs (most viewed)
     */
    public function popular()
    {
        $blogs = Blog::published()
            ->orderBy('view_count', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'message' => 'Lấy danh sách blog phổ biến thành công',
            'data' => $blogs
        ]);
    }
}
