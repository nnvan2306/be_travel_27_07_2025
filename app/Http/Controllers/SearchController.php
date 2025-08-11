<?php

namespace App\Http\Controllers;

use App\Models\Tour;
use App\Models\Blog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SearchController extends Controller
{
    /**
     * Global search for both tours and blogs
     */
    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2',
            'limit' => 'nullable|integer|min:1|max:50',
            'page' => 'nullable|integer|min:1',
        ]);

        $query = $request->input('q');
        $limit = $request->input('limit', 10);
        $page = $request->input('page', 1);

        // Get tours and blogs separately
        $tours = $this->getTours($query, $limit, $page);
        $blogs = $this->getBlogs($query, $limit, $page);

        $results = [
            'tours' => $tours,
            'blogs' => $blogs
        ];

        return response()->json([
            'success' => true,
            'data' => $results,
            'meta' => [
                'tour_count' => count($tours),
                'blog_count' => count($blogs),
                'total_count' => count($tours) + count($blogs),
                'query' => $query
            ]
        ]);
    }

    /**
     * Search specifically for tours
     */
    public function searchTours(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2',
            'limit' => 'nullable|integer|min:1|max:50',
            'page' => 'nullable|integer|min:1',
            'category_id' => 'nullable|integer',
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0',
            'sort_by' => 'nullable|string|in:price,created_at',
            'sort_dir' => 'nullable|string|in:asc,desc',
        ]);

        $searchQuery = $request->input('q');
        $limit = $request->input('limit', 10);
        $page = $request->input('page', 1);
        $categoryId = $request->input('category_id');
        $minPrice = $request->input('min_price');
        $maxPrice = $request->input('max_price');
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');

        // Lấy tất cả các cột có trong bảng tours
        $columns = Schema::getColumnListing('tours');

        $toursQuery = Tour::query();

        // Áp dụng tìm kiếm trên tất cả các cột văn bản
        $toursQuery->where(function ($query) use ($searchQuery, $columns) {
            // Danh sách các cột có thể tìm kiếm text
            $textColumns = array_intersect($columns, [
                'title',
                'name',
                'description',
                'short_description',
                'content',
                'summary',
                'overview',
                'highlights'
            ]);

            $firstColumn = true;
            foreach ($textColumns as $column) {
                if ($firstColumn) {
                    $query->where($column, 'LIKE', "%{$searchQuery}%");
                    $firstColumn = false;
                } else {
                    $query->orWhere($column, 'LIKE', "%{$searchQuery}%");
                }
            }

            // Tìm kiếm trong destinations nếu relationship tồn tại
            try {
                $query->orWhereHas('destinations', function ($q) use ($searchQuery) {
                    $q->where('name', 'LIKE', "%{$searchQuery}%");
                });
            } catch (\Exception $e) {
                // Ignore if relationship doesn't exist
            }
        });

        // Apply filters
        if ($categoryId) {
            $toursQuery->where('tour_category_id', $categoryId);
        }

        if ($minPrice) {
            $toursQuery->where('price', '>=', $minPrice);
        }

        if ($maxPrice) {
            $toursQuery->where('price', '<=', $maxPrice);
        }

        // Apply sorting (make sure column exists)
        if (in_array($sortBy, $columns)) {
            $toursQuery->orderBy($sortBy, $sortDir);
        } else {
            $toursQuery->orderBy('created_at', $sortDir);
        }

        // Truy vấn với tất cả các relationship có thể tồn tại
        try {
            $tours = $toursQuery->with(['category', 'destinations', 'schedules'])
                ->paginate($limit, ['*'], 'page', $page);
        } catch (\Exception $e) {
            // Fallback nếu relationship không tồn tại
            $tours = $toursQuery->paginate($limit, ['*'], 'page', $page);
        }

        return response()->json([
            'success' => true,
            'data' => $tours->items(),
            'meta' => [
                'current_page' => $tours->currentPage(),
                'last_page' => $tours->lastPage(),
                'per_page' => $tours->perPage(),
                'total' => $tours->total(),
                'query' => $searchQuery
            ]
        ]);
    }

    /**
     * Search specifically for blogs
     */
    public function searchBlogs(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2',
            'limit' => 'nullable|integer|min:1|max:50',
            'page' => 'nullable|integer|min:1',
            'tags' => 'nullable|string',
            'sort_by' => 'nullable|string|in:created_at,title',
            'sort_dir' => 'nullable|string|in:asc,desc',
        ]);

        $searchQuery = $request->input('q');
        $limit = $request->input('limit', 10);
        $page = $request->input('page', 1);
        $tags = $request->input('tags');
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');

        // Lấy tất cả các cột có trong bảng blogs
        $columns = Schema::getColumnListing('blogs');

        $blogsQuery = Blog::query();

        // Tìm kiếm bình thường thay vì full-text search
        $blogsQuery->where(function ($q) use ($searchQuery, $columns) {
            $textColumns = array_intersect($columns, [
                'title',
                'description',
                'markdown',
                'content',
                'tags',
                'location',
                'excerpt'
            ]);

            $firstColumn = true;
            foreach ($textColumns as $column) {
                if ($firstColumn) {
                    $q->where($column, 'LIKE', "%{$searchQuery}%");
                    $firstColumn = false;
                } else {
                    $q->orWhere($column, 'LIKE', "%{$searchQuery}%");
                }
            }
        });

        // Chỉ lấy blogs đã được publish (nếu cột status tồn tại)
        if (in_array('status', $columns)) {
            $blogsQuery->where('status', 'published');
        }

        // Filter by tags
        if ($tags && in_array('tags', $columns)) {
            $tagArray = explode(',', $tags);
            foreach ($tagArray as $tag) {
                $blogsQuery->where('tags', 'LIKE', "%{$tag}%");
            }
        }

        // Apply sorting (make sure column exists)
        if (in_array($sortBy, $columns)) {
            $blogsQuery->orderBy($sortBy, $sortDir);
        } else {
            $blogsQuery->orderBy('created_at', $sortDir);
        }

        // Get paginated results
        $blogs = $blogsQuery->paginate($limit, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'data' => $blogs->items(),
            'meta' => [
                'current_page' => $blogs->currentPage(),
                'last_page' => $blogs->lastPage(),
                'per_page' => $blogs->perPage(),
                'total' => $blogs->total(),
                'query' => $searchQuery
            ]
        ]);
    }

    /**
     * Helper method to get tours for global search
     */
    private function getTours($searchQuery, $limit, $page)
    {
        // Lấy tất cả các cột trong bảng
        $columns = Schema::getColumnListing('tours');

        $query = Tour::query();

        // Tìm kiếm trên các cột có thể là text
        $query->where(function ($q) use ($searchQuery, $columns) {
            $textColumns = array_intersect($columns, [
                'title',
                'name',
                'description',
                'short_description',
                'content',
                'summary',
                'overview',
                'highlights'
            ]);

            $firstColumn = true;
            foreach ($textColumns as $column) {
                if ($firstColumn) {
                    $q->where($column, 'LIKE', "%{$searchQuery}%");
                    $firstColumn = false;
                } else {
                    $q->orWhere($column, 'LIKE', "%{$searchQuery}%");
                }
            }
        });

        try {
            return $query->with(['category'])
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();
        } catch (\Exception $e) {
            // Fallback nếu relationship không tồn tại
            return $query->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();
        }
    }

    /**
     * Helper method to get blogs for global search
     */
    private function getBlogs($searchQuery, $limit, $page)
    {
        // Lấy tất cả các cột trong bảng
        $columns = Schema::getColumnListing('blogs');

        $query = Blog::query();

        // Tìm kiếm bình thường thay vì full-text search
        $query->where(function ($q) use ($searchQuery, $columns) {
            $textColumns = array_intersect($columns, [
                'title',
                'description',
                'markdown',
                'content',
                'tags',
                'location',
                'excerpt'
            ]);

            $firstColumn = true;
            foreach ($textColumns as $column) {
                if ($firstColumn) {
                    $q->where($column, 'LIKE', "%{$searchQuery}%");
                    $firstColumn = false;
                } else {
                    $q->orWhere($column, 'LIKE', "%{$searchQuery}%");
                }
            }
        });

        // Chỉ lấy blogs đã được publish (nếu cột status tồn tại)
        if (in_array('status', $columns)) {
            $query->where('status', 'published');
        }

        return $query->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
