<?php

namespace App\Http\Controllers;

use App\Models\Tour;
use App\Models\Album;
use App\Models\AlbumImage;
use App\Models\TourSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TourController extends Controller
{
    // Toggle status (active/inactive) cho tour
    public function toggleStatus($id)
    {
        $tour = Tour::findOrFail($id);
        if ($tour->is_deleted === self::STATUS_INACTIVE) {
            // Nếu đang bị ẩn thì bật lại
            $tour->is_deleted = self::STATUS_ACTIVE;
            $message = 'Tour đã được kích hoạt';
        } else {
            // Nếu đang active thì ẩn đi
            $tour->is_deleted = self::STATUS_INACTIVE;
            $message = 'Tour đã được vô hiệu hóa';
        }
        $tour->save();
        return response()->json([
            'message' => $message,
            'tour' => $tour,
        ]);
    }
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';

    // Danh sách tour
   public function index(Request $request)
{
    $query = Tour::with(['album.images', 'category', 'destinations', 'schedules', 'guide', 'busRoute']);

    // Lấy user từ token nếu có (chỉ có khi gọi qua route có middleware auth:sanctum)
    $user = null;
    if (auth('sanctum')->check()) {
        $user = auth('sanctum')->user();
    } else if (method_exists($request, 'user')) {
        $user = $request->user();
    }

    // Nếu là admin thì lấy tất cả, còn lại chỉ lấy tour đang hoạt động
    if (!$user || ($user->role !== 'admin' && $user->role !== 'superadmin')) {
        $query->where('is_deleted', self::STATUS_ACTIVE);
    }

    // Lọc theo category_id nếu có
    if ($request->has('category_id')) {
        $query->where('category_id', $request->category_id);
    }

    // Lọc theo price nếu có (ví dụ: price_min, price_max)
    if ($request->has('price_min')) {
        $query->where('price', '>=', $request->price_min);
    }
    if ($request->has('price_max')) {
        $query->where('price', '<=', $request->price_max);
    }

    $tours = $query->get();

    return response()->json($tours);
}

    // Tạo mới tour
    public function store(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:tour_categories,category_id',
            'tour_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'itinerary' => 'nullable|string',
            'price' => 'required|numeric',
            'discount_price' => 'nullable|numeric',
            'duration' => 'nullable|string',
            'min_people' => 'nullable|integer|min:1',
            'status' => 'nullable|in:visible,hidden',
            'image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'destination_ids' => 'nullable|array',
            'destination_ids.*' => 'exists:destinations,destination_id',
            'guide_id' => 'nullable|exists:guides,guide_id',
            'bus_route_id' => 'nullable|exists:bus_routes,bus_route_id',
            'schedules' => 'nullable|array',
            'schedules.*.day' => 'nullable|integer|min:1',
            'schedules.*.title' => 'nullable|string|max:255',
            'schedules.*.activity_description' => 'nullable|string',
        ]);

        // Sử dụng transaction để đảm bảo toàn vẹn dữ liệu
        DB::beginTransaction();
        try {
            // Tạo album
            $album = Album::create([
                'title' => 'Album cho tour ' . $request->tour_name,
                'is_deleted' => self::STATUS_ACTIVE,
            ]);

            // Lưu ảnh chính
            try {
                $originalName = $request->file('image')->getClientOriginalName();
                $sanitizedName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
                $fileName = time() . '_' . $sanitizedName;
                $imagePath = $request->file('image')->storeAs('tours', $fileName, 'public');
            } catch (\Exception $e) {
                return response()->json(['message' => 'Lỗi khi upload ảnh chính: ' . $e->getMessage()], 500);
            }

            // Tạo slug duy nhất
            $slug = Str::slug($request->tour_name);
            if (Tour::where('slug', $slug)->exists()) {
                $slug .= '-' . uniqid();
            }

            // Tạo tour
            $tour = Tour::create([
                'category_id' => $request->category_id,
                'album_id' => $album->album_id,
                'guide_id' => $request->guide_id,
                'bus_route_id' => $request->bus_route_id,
                'tour_name' => $request->tour_name,
                'description' => $request->description,
                'itinerary' => $request->itinerary,
                'price' => $request->price,
                'discount_price' => $request->discount_price,
                'duration' => $request->duration,
                'min_people' => $request->min_people ?? 2,
                'status' => $request->status ?? 'visible',
                'image' => $imagePath,
                'slug' => $slug,
                'is_deleted' => self::STATUS_ACTIVE,
            ]);

            // Lưu ảnh phụ
            if ($request->hasFile('images')) {
                try {
                    foreach ($request->file('images') as $img) {
                        // Sanitize tên file
                        $originalName = $img->getClientOriginalName();
                        $sanitizedName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
                        $fileName = time() . '_' . $sanitizedName;
                        
                        $path = $img->storeAs('album_images', $fileName, 'public');
                        AlbumImage::create([
                            'album_id' => $album->album_id,
                            'image_url' => $path,
                            'caption' => null,
                            'is_deleted' => self::STATUS_ACTIVE,
                        ]);
                    }
                } catch (\Exception $e) {
                    return response()->json(['message' => 'Lỗi khi upload ảnh phụ: ' . $e->getMessage()], 500);
                }
            }

            // Lưu lịch trình
            if ($request->has('schedules')) {
                foreach ($request->schedules as $schedule) {
                    if (isset($schedule['title']) && !empty(trim($schedule['title']))) {
                        TourSchedule::create([
                            'tour_id' => $tour->tour_id,
                            'day' => $schedule['day'] ?? 1,
                            'start_time' => null,
                            'end_time' => null,
                            'title' => trim($schedule['title']),
                            'activity_description' => isset($schedule['activity_description']) ? trim($schedule['activity_description']) : null,
                            'destination_id' => null,
                        ]);
                    }
                }
            }

            // Gắn điểm đến
            if ($request->has('destination_ids')) {
                $tour->destinations()->sync($request->destination_ids);
            }

            DB::commit();
            return response()->json([
                'message' => 'Tạo tour thành công',
                'tour' => $tour->load(['category', 'album.images', 'destinations', 'schedules', 'guide', 'busRoute']),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Lỗi khi tạo tour: ' . $e->getMessage()], 500);
        }
    }

    // Xem chi tiết
    public function show($id)
    {
        $tour = Tour::with([
            'category',
            'album.images',
            'destinations',
            'schedules.destination',
            'guide',
            'busRoute'
        ])->findOrFail($id);

        if ($tour->is_deleted === self::STATUS_INACTIVE) {
            return response()->json(['message' => 'Tour đã bị xoá'], 404);
        }

        $schedules = $tour->schedules->map(function ($schedule) {
            $data = $schedule->toArray();
            return $data;
        });

        $tourArray = $tour->toArray();
        $tourArray['schedules'] = $schedules;

        return response()->json($tourArray);
    }

    // Cập nhật tour
    public function update(Request $request, $id)
    {
        $tour = Tour::with('album.images')->findOrFail($id);

        if ($tour->is_deleted === self::STATUS_INACTIVE) {
            return response()->json(['message' => 'Tour đã bị xoá'], 404);
        }

        $request->validate([
            'category_id' => 'nullable|exists:tour_categories,category_id',
            'tour_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'itinerary' => 'nullable|string',
            'price' => 'nullable|numeric',
            'discount_price' => 'nullable|numeric',
            'duration' => 'nullable|string',
            'min_people' => 'nullable|integer|min:1',
            'status' => 'nullable|in:visible,hidden',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'destination_ids' => 'nullable|array',
            'destination_ids.*' => 'exists:destinations,destination_id',
            'guide_id' => 'nullable|exists:guides,guide_id',
            'bus_route_id' => 'nullable|exists:bus_routes,bus_route_id',
            'schedules.*.day' => 'nullable|integer|min:1',
            'schedules.*.title' => 'nullable|string|max:255',
            'schedules.*.activity_description' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Debug: Log thông tin request
            Log::info('Tour update request', [
                'has_image' => $request->hasFile('image'),
                'has_images' => $request->hasFile('images'),
                'all_files' => $request->allFiles(),
                'content_type' => $request->header('Content-Type')
            ]);

            // Cập nhật ảnh chính
            if ($request->hasFile('image')) {
                try {
                    Log::info('Processing main image', [
                        'original_name' => $request->file('image')->getClientOriginalName(),
                        'size' => $request->file('image')->getSize(),
                        'mime_type' => $request->file('image')->getMimeType()
                    ]);

                    // Xóa ảnh cũ nếu tồn tại
                    if ($tour->image && Storage::disk('public')->exists($tour->image)) {
                        Storage::disk('public')->delete($tour->image);
                        Log::info('Deleted old image', ['path' => $tour->image]);
                    }
                    
                    // Sanitize tên file
                    $originalName = $request->file('image')->getClientOriginalName();
                    $sanitizedName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
                    $fileName = time() . '_' . $sanitizedName;
                    
                    // Lưu ảnh mới
                    $imagePath = $request->file('image')->storeAs('tours', $fileName, 'public');
                    $tour->image = $imagePath;
                    
                    Log::info('Main image uploaded successfully', ['path' => $imagePath]);
                } catch (\Exception $e) {
                    Log::error('Error uploading main image', ['error' => $e->getMessage()]);
                    return response()->json(['message' => 'Lỗi khi upload ảnh chính: ' . $e->getMessage()], 500);
                }
            }

            // Cập nhật thông tin tour
            $hasUpdates = false;
            
            if ($request->has('category_id')) {
                $tour->category_id = $request->category_id;
                $hasUpdates = true;
            }
            
            if ($request->has('tour_name') && $request->tour_name !== $tour->tour_name) {
                $tour->tour_name = $request->tour_name;
                
                // Cập nhật slug khi tour_name thay đổi
                $slug = Str::slug($request->tour_name);
                if (Tour::where('slug', $slug)->where('tour_id', '!=', $tour->tour_id)->exists()) {
                    $slug .= '-' . uniqid();
                }
                $tour->slug = $slug;
                $hasUpdates = true;
            }
            
            if ($request->has('description')) {
                $tour->description = $request->description;
                $hasUpdates = true;
            }
            
            if ($request->has('itinerary')) {
                $tour->itinerary = $request->itinerary;
                $hasUpdates = true;
            }
            
            if ($request->has('price')) {
                $tour->price = $request->price;
                $hasUpdates = true;
            }
            
            if ($request->has('discount_price')) {
                $tour->discount_price = $request->discount_price ?: null;
                $hasUpdates = true;
            }
            
            if ($request->has('duration')) {
                $tour->duration = $request->duration;
                $hasUpdates = true;
            }
            
            if ($request->has('min_people')) {
                $tour->min_people = $request->min_people;
                $hasUpdates = true;
            }
            
            if ($request->has('status')) {
                $tour->status = $request->status;
                $hasUpdates = true;
            }
            
            if ($request->has('guide_id')) {
                $tour->guide_id = $request->guide_id ?: null;
                $hasUpdates = true;
            }
            
            if ($request->has('bus_route_id')) {
                $tour->bus_route_id = $request->bus_route_id ?: null;
                $hasUpdates = true;
            }

            // Lưu thông tin cơ bản nếu có thay đổi
            if ($hasUpdates) {
                $tour->save();
                \Log::info('Tour basic info updated successfully', [
                    'tour_id' => $tour->tour_id,
                    'updated_at' => $tour->updated_at
                ]);
            }

            // Cập nhật điểm đến
            if ($request->has('destination_ids')) {
                $tour->destinations()->sync($request->destination_ids);
            }

            // Cập nhật ảnh cover
            if ($request->hasFile('cover_image')) {
                try {
                    \Log::info('Processing cover image');
                    
                    // Xóa ảnh cover cũ
                    if ($tour->cover_image && Storage::disk('public')->exists($tour->cover_image)) {
                        Storage::disk('public')->delete($tour->cover_image);
                        \Log::info('Deleted old cover image', ['path' => $tour->cover_image]);
                    }

                    $coverImage = $request->file('cover_image');
                    $coverOriginalName = $coverImage->getClientOriginalName();
                    $sanitizedCoverName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $coverOriginalName);
                    $coverFileName = time() . '_cover_' . $sanitizedCoverName;
                    
                    $coverPath = $coverImage->storeAs('tour_covers', $coverFileName, 'public');
                    $tour->cover_image = $coverPath;
                    $tour->save();
                    \Log::info('New cover image uploaded', ['path' => $coverPath]);
                } catch (\Exception $e) {
                    \Log::error('Error updating cover image', ['error' => $e->getMessage()]);
                    throw new \Exception('Lỗi khi cập nhật ảnh cover: ' . $e->getMessage());
                }
            }

            // Cập nhật ảnh phụ
            if ($request->hasFile('images') || $request->has('old_images')) {
                try {
                    if ($tour->album) {
                        \Log::info('Processing album images', [
                            'has_new_images' => $request->hasFile('images'),
                            'has_old_images' => $request->has('old_images')
                        ]);

                        // Xóa tất cả ảnh cũ
                        foreach ($tour->album->images as $img) {
                            if (Storage::disk('public')->exists($img->image_url)) {
                                Storage::disk('public')->delete($img->image_url);
                            }
                            $img->delete();
                        }

                        // Thêm lại ảnh cũ được giữ lại
                        if ($request->has('old_images')) {
                            $oldImages = $request->get('old_images');
                            if (is_array($oldImages)) {
                                foreach ($oldImages as $oldImagePath) {
                                    if (Storage::disk('public')->exists($oldImagePath)) {
                                        AlbumImage::create([
                                            'album_id' => $tour->album_id,
                                            'image_url' => $oldImagePath,
                                            'caption' => null,
                                            'is_deleted' => self::STATUS_ACTIVE,
                                        ]);
                                        \Log::info('Kept old album image', ['path' => $oldImagePath]);
                                    }
                                }
                            }
                        }

                        // Upload ảnh mới
                        if ($request->hasFile('images')) {
                            foreach ($request->file('images') as $index => $img) {
                                $originalName = $img->getClientOriginalName();
                                $sanitizedName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
                                $fileName = time() . '_' . $index . '_' . $sanitizedName;
                                $path = $img->storeAs('album_images', $fileName, 'public');
                                
                                AlbumImage::create([
                                    'album_id' => $tour->album_id,
                                    'image_url' => $path,
                                    'caption' => null,
                                    'is_deleted' => self::STATUS_ACTIVE,
                                ]);
                                \Log::info('New album image uploaded', ['path' => $path]);
                            }
                        }
                    }
                } catch (\Exception $e) {
                    \Log::error('Error updating album images', ['error' => $e->getMessage()]);
                    throw new \Exception('Lỗi khi cập nhật ảnh album: ' . $e->getMessage());
                }
            }

            // Cập nhật lịch trình
            if ($request->has('schedules')) {
                \Log::info('Updating schedules', [
                    'tour_id' => $tour->tour_id,
                    'schedules_data' => $request->get('schedules')
                ]);
                
                // Xóa tất cả schedule cũ
                $deletedCount = $tour->schedules()->delete();
                \Log::info('Deleted old schedules', ['count' => $deletedCount]);
                
                // Thêm schedule mới
                $schedules = $request->get('schedules');
                if (is_array($schedules)) {
                    foreach ($schedules as $index => $schedule) {
                        if (isset($schedule['title']) && !empty(trim($schedule['title']))) {
                            $newSchedule = TourSchedule::create([
                                'tour_id' => $tour->tour_id,
                                'day' => isset($schedule['day']) ? (int)$schedule['day'] : ($index + 1),
                                'start_time' => null, // Không cần start_time nữa
                                'end_time' => null,   // Không cần end_time nữa
                                'title' => trim($schedule['title']),
                                'activity_description' => isset($schedule['activity_description']) ? trim($schedule['activity_description']) : null,
                                'destination_id' => null, // Không cần destination_id nữa
                            ]);
                            \Log::info('Schedule created', [
                                'schedule_id' => $newSchedule->schedule_id,
                                'day' => $newSchedule->day,
                                'title' => $newSchedule->title
                            ]);
                        }
                    }
                }
            }

            DB::commit();
            return response()->json([
                'message' => 'Cập nhật tour thành công',
                'tour' => $tour->load(['category', 'album.images', 'destinations', 'schedules', 'guide', 'busRoute']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Lỗi khi cập nhật tour: ' . $e->getMessage()], 500);
        }
    }

    // Xoá mềm / khôi phục
    public function softDelete($id)
    {
        $tour = Tour::findOrFail($id);
        $tour->is_deleted = $tour->is_deleted === self::STATUS_ACTIVE ? self::STATUS_INACTIVE : self::STATUS_ACTIVE;
        $tour->save();

        return response()->json([
            'message' => $tour->is_deleted === self::STATUS_INACTIVE ? 'Tour đã được ẩn' : 'Tour đã khôi phục',
            'tour' => $tour,
        ]);
    }

    // Xoá vĩnh viễn
    public function destroy($id)
    {
        $tour = Tour::with('album.images')->findOrFail($id);

        DB::beginTransaction();
        try {
            if ($tour->image && Storage::disk('public')->exists($tour->image)) {
                Storage::disk('public')->delete($tour->image);
            }

            if ($tour->album && $tour->album->images) {
                foreach ($tour->album->images as $img) {
                    if (Storage::disk('public')->exists($img->image_url)) {
                        Storage::disk('public')->delete($img->image_url);
                    }
                    $img->delete();
                }
            }

            $tour->schedules()->delete();
            $tour->delete();

            DB::commit();
            return response()->json(['message' => 'Đã xoá tour vĩnh viễn']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Lỗi khi xoá tour: ' . $e->getMessage()], 500);
        }
    }

    // Danh sách tour bị xoá mềm
    public function trashed()
    {
        $tours = Tour::with(['album.images', 'category', 'destinations', 'schedules', 'guide', 'busRoute'])
            ->where('is_deleted', self::STATUS_INACTIVE)
            ->get();

        return response()->json($tours);
    }

    // Tìm tour theo slug
    public function getBySlug($slug)
    {
        $tour = Tour::with(['category', 'album.images', 'destinations', 'schedules', 'guide', 'busRoute'])
            ->where('slug', $slug)
            ->where('is_deleted', self::STATUS_ACTIVE)
            ->first();

        if (!$tour) {
            return response()->json(['message' => 'Không tìm thấy tour'], 404);
        }

        return response()->json($tour);
    }

        // Toggle status (active/inactive) cho tour
}