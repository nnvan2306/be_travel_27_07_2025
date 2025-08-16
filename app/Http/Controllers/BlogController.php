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
        try {
            return response()->json([
                'message' => 'Lấy form tạo blog thành công',
                'data' => [
                    'blog' => [
                        'title' => '',
                        'description' => '',
                        'markdown' => '',
                        'location' => '',
                        'thumbnail' => null,
                        'status' => 'published'
                    ],
                    'validation_rules' => [
                        'title' => 'required|string|max:255',
                        'description' => 'nullable|string',
                        'markdown' => 'required|string',
                        'location' => 'nullable|string|max:255',
                        'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                        'status' => 'nullable|in:published,draft'
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Lỗi khi lấy form tạo blog: ' . $e->getMessage()], 500);
        }
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
            'status' => 'nullable|in:published,draft',
            'tags' => 'nullable|string|max:255' // Thêm validation cho tags
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $blogData = $request->only(['title', 'description', 'markdown', 'location', 'status', 'tags']);
            $blogData['status'] = $blogData['status'] ?? 'published';

            // Xử lý tags: chuẩn hóa format (ví dụ: chuyển đổi dấu phẩy và khoảng trắng)
            if (isset($blogData['tags'])) {
                // Loại bỏ khoảng trắng thừa, đảm bảo tags phân tách bằng dấu phẩy
                $blogData['tags'] = preg_replace('/\s*,\s*/', ',', trim($blogData['tags']));
            }

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

        // Tăng số lượt xem (chỉ với blog published hoặc user có quyền)
        if ($blog->status === 'published' || (auth()->check() && auth()->user()->role === 'admin')) {
            $blog->incrementViewCount();
        }

        return response()->json([
            'message' => 'Lấy chi tiết blog thành công',
            'data' => $blog->fresh() // Lấy dữ liệu mới nhất sau khi tăng view
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

        // Tăng số lượt xem (chỉ với blog published hoặc user có quyền)
        if ($blog->status === 'published' || (auth()->check() && auth()->user()->role === 'admin')) {
            $blog->incrementViewCount();
        }

        return response()->json([
            'message' => 'Lấy chi tiết blog thành công',
            'data' => $blog->fresh() // Lấy dữ liệu mới nhất sau khi tăng view
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        try {
            $blog = Blog::find($id);

            if (!$blog) {
                return response()->json(['message' => 'Blog không tồn tại'], 404);
            }

            return response()->json([
                'message' => 'Lấy form chỉnh sửa blog thành công',
                'data' => [
                    'blog' => $blog,
                    'validation_rules' => [
                        'title' => 'sometimes|required|string|max:255',
                        'description' => 'nullable|string',
                        'markdown' => 'sometimes|required|string',
                        'location' => 'nullable|string|max:255',
                        'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                        'status' => 'nullable|in:published,draft'
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Lỗi khi lấy form chỉnh sửa blog: ' . $e->getMessage()], 500);
        }
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

        \Log::info('Update blog request data:', $request->all());

        $validator = Validator::make($request->all(), [
            'description' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'nullable|in:published,draft',
            'tags' => 'nullable|string|max:255' // Thêm validation cho tags
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Lấy và kiểm tra dữ liệu
            $blogData = $request->only(['title', 'description', 'markdown', 'location', 'status', 'tags']);

            // Xử lý tags
            if (isset($blogData['tags'])) {
                // Loại bỏ khoảng trắng thừa, đảm bảo tags phân tách bằng dấu phẩy
                $blogData['tags'] = preg_replace('/\s*,\s*/', ',', trim($blogData['tags']));
            }

            \Log::info('Blog data before update:', $blogData);

            // Xử lý thumbnail nếu có
            if ($request->hasFile('thumbnail')) {
                try {
                    // Xóa thumbnail cũ nếu có
                    if ($blog->thumbnail && Storage::disk('public')->exists($blog->thumbnail)) {
                        Storage::disk('public')->delete($blog->thumbnail);
                    }

                    $originalName = $request->file('thumbnail')->getClientOriginalName();
                    $sanitizedName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
                    $fileName = time() . '_' . $sanitizedName;
                    $thumbnailPath = $request->file('thumbnail')->storeAs('blogs', $fileName, 'public');
                    $blogData['thumbnail'] = $thumbnailPath;
                } catch (\Exception $e) {
                    \Log::error('Thumbnail upload error: ' . $e->getMessage());
                    return response()->json(['message' => 'Lỗi khi upload thumbnail: ' . $e->getMessage()], 500);
                }
            }

            // Cập nhật slug nếu title thay đổi
            if (isset($blogData['title']) && $blogData['title'] !== $blog->title) {
                $slug = Str::slug($blogData['title']);
                $originalSlug = $slug;
                $counter = 1;

                while (Blog::where('slug', $slug)->where('id', '!=', $blog->id)->exists()) {
                    $slug = $originalSlug . '-' . $counter++;
                }
                $blogData['slug'] = $slug;
            }

            // Thực hiện update và kiểm tra kết quả
            $updated = $blog->update($blogData);

            if (!$updated) {
                throw new \Exception('Không thể cập nhật blog');
            }

            // Refresh model để lấy data mới nhất
            $blog = $blog->fresh();

            \Log::info('Blog updated successfully:', $blog->toArray());

            return response()->json([
                'message' => 'Cập nhật blog thành công',
                'data' => $blog
            ]);

        } catch (\Exception $e) {
            \Log::error('Blog update error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Lỗi khi cập nhật blog: ' . $e->getMessage()
            ], 500);
        }
    }


    public function updateWithFiles(Request $request, $id)
    {
        $blog = Blog::find($id);

        if (!$blog) {
            return response()->json(['message' => 'Blog không tồn tại'], 404);
        }

        // Log request data để debug
        \Log::info('Update blog request data:', $request->all());

        $validator = Validator::make($request->all(), [
            'description' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'nullable|in:published,draft',
            'tags' => 'nullable|string|max:255' // Thêm validation cho tags
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Lấy và kiểm tra dữ liệu
            $blogData = $request->only(['title', 'description', 'markdown', 'location', 'status', 'tags']);

            // Xử lý tags
            if (isset($blogData['tags'])) {
                // Loại bỏ khoảng trắng thừa, đảm bảo tags phân tách bằng dấu phẩy
                $blogData['tags'] = preg_replace('/\s*,\s*/', ',', trim($blogData['tags']));
            }

            \Log::info('Blog data before update:', $blogData);

            // Xử lý thumbnail nếu có
            if ($request->hasFile('thumbnail')) {
                try {
                    // Xóa thumbnail cũ nếu có
                    if ($blog->thumbnail && Storage::disk('public')->exists($blog->thumbnail)) {
                        Storage::disk('public')->delete($blog->thumbnail);
                    }

                    $originalName = $request->file('thumbnail')->getClientOriginalName();
                    $sanitizedName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
                    $fileName = time() . '_' . $sanitizedName;
                    $thumbnailPath = $request->file('thumbnail')->storeAs('blogs', $fileName, 'public');
                    $blogData['thumbnail'] = $thumbnailPath;
                } catch (\Exception $e) {
                    \Log::error('Thumbnail upload error: ' . $e->getMessage());
                    return response()->json(['message' => 'Lỗi khi upload thumbnail: ' . $e->getMessage()], 500);
                }
            }

            // Cập nhật slug nếu title thay đổi
            if (isset($blogData['title']) && $blogData['title'] !== $blog->title) {
                $slug = Str::slug($blogData['title']);
                $originalSlug = $slug;
                $counter = 1;

                while (Blog::where('slug', $slug)->where('id', '!=', $blog->id)->exists()) {
                    $slug = $originalSlug . '-' . $counter++;
                }
                $blogData['slug'] = $slug;
            }

            // Thực hiện update và kiểm tra kết quả
            $updated = $blog->update($blogData);

            if (!$updated) {
                throw new \Exception('Không thể cập nhật blog');
            }

            // Refresh model để lấy data mới nhất
            $blog = $blog->fresh();

            // Log kết quả update
            \Log::info('Blog updated successfully:', $blog->toArray());

            return response()->json([
                'message' => 'Cập nhật blog thành công',
                'data' => $blog
            ]);

        } catch (\Exception $e) {
            \Log::error('Blog update error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Lỗi khi cập nhật blog: ' . $e->getMessage()
            ], 500);
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

    /**
     * Lọc blog theo tag
     */
    public function getByTag(Request $request, $tag)
    {
        try {
            $query = Blog::query();

            // Lọc blog có chứa tag được chỉ định
            $query->where(function ($q) use ($tag) {
                // Tìm chính xác tag
                $q->where('tags', $tag)
                    // Hoặc tag ở đầu danh sách, theo sau là dấu phẩy
                    ->orWhere('tags', 'LIKE', $tag . ',%')
                    // Hoặc tag ở cuối danh sách, phía trước là dấu phẩy
                    ->orWhere('tags', 'LIKE', '%,' . $tag)
                    // Hoặc tag ở giữa danh sách, phía trước và sau là dấu phẩy
                    ->orWhere('tags', 'LIKE', '%,' . $tag . ',%');
            });

            // Chỉ lấy blog đã publish nếu không phải admin
            if (!$request->user() || !$request->user()->isAdmin()) {
                $query->where('status', 'published');
            }

            // Sắp xếp
            $query->orderBy('created_at', 'desc');

            // Phân trang
            $perPage = $request->get('per_page', 10);
            $blogs = $query->paginate($perPage);

            return response()->json([
                'message' => 'Lấy danh sách blog theo tag thành công',
                'data' => $blogs
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Lỗi khi lấy danh sách blog theo tag: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy danh sách tất cả các tags
     */
    public function getAllTags()
    {
        try {
            // Lấy tất cả các blog có tags
            $blogs = Blog::whereNotNull('tags')->get(['tags']);

            // Tạo mảng chứa tất cả tags
            $allTags = [];

            foreach ($blogs as $blog) {
                $tags = explode(',', $blog->tags);
                foreach ($tags as $tag) {
                    $tag = trim($tag);
                    if (!empty($tag) && !in_array($tag, $allTags)) {
                        $allTags[] = $tag;
                    }
                }
            }

            // Sắp xếp tags theo thứ tự alphabet
            sort($allTags);

            return response()->json([
                'message' => 'Lấy danh sách tags thành công',
                'data' => $allTags
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Lỗi khi lấy danh sách tags: ' . $e->getMessage()
            ], 500);
        }
    }
}
