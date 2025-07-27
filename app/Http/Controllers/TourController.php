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

class TourController extends Controller
{
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';

    // Danh sách tour
    public function index()
    {
        $tours = Tour::with(['album.images', 'category', 'destinations', 'schedules', 'guide', 'busRoute'])
            ->where('is_deleted', self::STATUS_ACTIVE)
            ->get();

        return response()->json($tours);
    }

    // Tạo mới tour
    public function store(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:destination_categories,category_id',
            'tour_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'itinerary' => 'nullable|string',
            'price' => 'required|numeric',
            'discount_price' => 'nullable|numeric',
            'duration' => 'nullable|string',
            'status' => 'nullable|in:visible,hidden',
            'image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'destination_ids' => 'nullable|array',
            'destination_ids.*' => 'exists:destinations,destination_id',
            'guide_id' => 'nullable|exists:guides,guide_id',
            'bus_route_id' => 'nullable|exists:bus_routes,bus_route_id',
            'schedules' => 'nullable|array',
            'schedules.*.day' => 'required|integer|min:1',
            'schedules.*.start_time' => 'required|date_format:H:i',
            'schedules.*.end_time' => 'required|date_format:H:i|after:schedules.*.start_time',
            'schedules.*.title' => 'required|string|max:255',
            'schedules.*.activity_description' => 'nullable|string',
            'schedules.*.destination_id' => 'nullable|exists:destinations,destination_id',
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
            $imagePath = $request->file('image')->store('tours', 'public');

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
                'status' => $request->status ?? 'visible',
                'image' => $imagePath,
                'slug' => $slug,
                'is_deleted' => self::STATUS_ACTIVE,
            ]);

            // Lưu ảnh phụ
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $img) {
                    $path = $img->store('album_images', 'public');
                    AlbumImage::create([
                        'album_id' => $album->album_id,
                        'image_url' => $path,
                        'caption' => null,
                        'is_deleted' => self::STATUS_ACTIVE,
                    ]);
                }
            }

            // Lưu lịch trình
            if ($request->has('schedules')) {
                foreach ($request->schedules as $schedule) {
                    TourSchedule::create([
                        'tour_id' => $tour->tour_id,
                        'day' => $schedule['day'],
                        'start_time' => $schedule['start_time'],
                        'end_time' => $schedule['end_time'],
                        'title' => $schedule['title'],
                        'activity_description' => $schedule['activity_description'] ?? null,
                        'destination_id' => $schedule['destination_id'] ?? null,
                    ]);
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
        $tour = Tour::with(['category', 'album.images', 'destinations', 'schedules', 'guide', 'busRoute'])->findOrFail($id);

        if ($tour->is_deleted === self::STATUS_INACTIVE) {
            return response()->json(['message' => 'Tour đã bị xoá'], 404);
        }

        return response()->json($tour);
    }

    // Cập nhật tour
    public function update(Request $request, $id)
    {
        $tour = Tour::with('album.images')->findOrFail($id);

        if ($tour->is_deleted === self::STATUS_INACTIVE) {
            return response()->json(['message' => 'Tour đã bị xoá'], 404);
        }

        $request->validate([
            'category_id' => 'exists:destination_categories,category_id',
            'tour_name' => 'string|max:255',
            'description' => 'nullable|string',
            'itinerary' => 'nullable|string',
            'price' => 'numeric',
            'discount_price' => 'nullable|numeric',
            'duration' => 'nullable|string',
            'status' => 'in:visible,hidden',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'destination_ids' => 'nullable|array',
            'destination_ids.*' => 'exists:destinations,destination_id',
            'guide_id' => 'nullable|exists:guides,guide_id',
            'bus_route_id' => 'nullable|exists:bus_routes,bus_route_id',
            'schedules' => 'nullable|array',
            'schedules.*.day' => 'required|integer|min:1',
            'schedules.*.start_time' => 'required|date_format:H:i',
            'schedules.*.end_time' => 'required|date_format:H:i|after:schedules.*.start_time',
            'schedules.*.title' => 'required|string|max:255',
            'schedules.*.activity_description' => 'nullable|string',
            'schedules.*.destination_id' => 'nullable|exists:destinations,destination_id',
        ]);

        DB::beginTransaction();
        try {
            // Cập nhật ảnh chính
            if ($request->hasFile('image')) {
                if ($tour->image && Storage::disk('public')->exists($tour->image)) {
                    Storage::disk('public')->delete($tour->image);
                }
                $tour->image = $request->file('image')->store('tours', 'public');
            }

            // Cập nhật slug nếu tour_name thay đổi
            if ($request->has('tour_name') && $request->tour_name !== $tour->tour_name) {
                $slug = Str::slug($request->tour_name);
                if (Tour::where('slug', $slug)->where('tour_id', '!=', $tour->tour_id)->exists()) {
                    $slug .= '-' . uniqid();
                }
                $tour->slug = $slug;
            }

            // Cập nhật thông tin tour
            $tour->update($request->only([
                'category_id',
                'tour_name',
                'description',
                'itinerary',
                'price',
                'discount_price',
                'duration',
                'status',
                'guide_id',
                'bus_route_id',
            ]));

            // Cập nhật điểm đến
            if ($request->has('destination_ids')) {
                $tour->destinations()->sync($request->destination_ids);
            }

            // Cập nhật ảnh phụ
            if ($request->hasFile('images') && $tour->album) {
                foreach ($tour->album->images as $img) {
                    if (Storage::disk('public')->exists($img->image_url)) {
                        Storage::disk('public')->delete($img->image_url);
                    }
                    $img->delete();
                }
                foreach ($request->file('images') as $img) {
                    $path = $img->store('album_images', 'public');
                    AlbumImage::create([
                        'album_id' => $tour->album_id,
                        'image_url' => $path,
                        'caption' => null,
                        'is_deleted' => self::STATUS_ACTIVE,
                    ]);
                }
            }

            // Cập nhật lịch trình
            if ($request->has('schedules')) {
                $tour->schedules()->delete();
                foreach ($request->schedules as $schedule) {
                    TourSchedule::create([
                        'tour_id' => $tour->tour_id,
                        'day' => $schedule['day'],
                        'start_time' => $schedule['start_time'],
                        'end_time' => $schedule['end_time'],
                        'title' => $schedule['title'],
                        'activity_description' => $schedule['activity_description'] ?? null,
                        'destination_id' => $schedule['destination_id'] ?? null,
                    ]);
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
}