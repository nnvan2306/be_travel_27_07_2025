<?php

namespace App\Http\Controllers;

use App\Models\{Destination, DestinationSection, Album, AlbumImage};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DestinationController extends Controller
{

    private function decodeSections($sections, $album_id)
    {
        return $sections->map(function ($section) use ($album_id) {
            // Nếu content là chuỗi JSON -> decode
            if (in_array($section->type, ['gallery', 'regionalDelicacies', 'highlight']) && is_string($section->content)) {
                $section->content = json_decode($section->content, true);
            }

            // Xử lý hiển thị đúng định dạng từng loại
            if ($section->type === 'experience') {
                $section->description = $section->content;
                unset($section->content);
            }

            if ($section->type === 'lastImage') {
                $section->image = $section->content
                    ? asset('storage/albums/' . $album_id . '/' . $section->content)
                    : null;
                unset($section->content);
            }

            if ($section->type === 'gallery' && is_array($section->content)) {
                $content = $section->content;
                $content = array_map(function ($img) use ($album_id) {
                    return asset('storage/albums/' . $album_id . '/' . $img);
                }, $content);
                $section->content = $content;
            }

            if ($section->type === 'regionalDelicacies' && is_array($section->content)) {
                if (isset($section->content['dishes']) && is_array($section->content['dishes'])) {
                    $content = $section->content; // ✅ lấy ra để sửa
                    foreach ($content['dishes'] as &$dish) {
                        if (!empty($dish['image'])) {
                            $dish['image'] = asset('storage/albums/' . $album_id . '/' . $dish['image']);
                        }
                    }
                    $section->content = $content; // ✅ gán lại để tránh lỗi
                }
            }
            if ($section->type === 'highlight' && is_array($section->content)) {
                $content = $section->content;
                foreach ($content as &$highlightItem) {
                    if (!empty($highlightItem['image'])) {
                        $highlightItem['image'] = asset('storage/albums/' . $album_id . '/' . $highlightItem['image']);
                    }
                }
                $section->content = $content;
            }


            return $section;
        });
    }
    public function index()
    {
        $destinations = Destination::with('sections') // Lấy tất cả (không lọc is_deleted)
            ->get()
            ->map(function ($dest) {
                $dest->img_banner_url = $dest->img_banner ? asset('storage/' . $dest->img_banner) : null;
                $dest->sections = $this->decodeSections($dest->sections, $dest->album_id);
                return $dest;
            });

        return response()->json($destinations);
    }
    private function makeSectionTitle(string $type): ?string
    {
        return match ($type) {
            'intro' => 'Giới thiệu',
            'highlight' => 'Điểm nổi bật',
            'gallery' => 'Bộ sưu tập',
            'experience' => 'Trải nghiệm',
            'lastImage' => 'Hình ảnh kết thúc',
            'regionalDelicacies' => 'Ẩm thực địa phương',
            default => null,
        };
    }
    public function show($id)
    {
        $destination = Destination::where('is_deleted', 'active')
            ->with([
                'sections',
                'album.images',
                'category'
            ])
            ->find($id);
            
        if (!$destination) {
            return response()->json(['message' => 'Không tìm thấy điểm đến'], 404);
        }

        // URL cho ảnh banner
        $destination->img_banner_url = $destination->img_banner
            ? asset('storage/' . $destination->img_banner)
            : null;

        // URL cho album images
        if ($destination->album && $destination->album->images) {
            foreach ($destination->album->images as $img) {
                $img->image_url = asset('storage/' . $img->image_url);
            }
        }

        // URL cho ảnh category thumbnail
        if ($destination->category && $destination->category->thumbnail) {
            $destination->category->thumbnail = asset('storage/' . $destination->category->thumbnail);
        }

        $destination->sections = $this->decodeSections($destination->sections, $destination->album_id);

        return response()->json($destination);
    }
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'category_id' => 'nullable|numeric',
            'description' => 'nullable|string',
            'area' => 'nullable|string|max:100',
            'imgBanner' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'sections' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Dữ liệu không hợp lệ', 'errors' => $validator->errors()], 422);
        }

        $sections = json_decode($request->input('sections'), true);
        if (!is_array($sections)) {
            return response()->json(['message' => 'Sections phải là mảng hợp lệ'], 422);
        }

        $albumId = null;
        $imagePath = null;

        // Tạo album nếu có ít nhất 1 ảnh
        if ($request->hasFile('imgBanner') || $request->hasFile('galleryImages') || $request->hasFile('delicacyImages') || $request->hasFile('lastImage')) {
            $album = Album::create([
                'title' => $request->name . ' - Album',
                'is_deleted' => 'active'
            ]);
            $albumId = $album->album_id;
        }

        // Lưu ảnh banner
        if ($request->hasFile('imgBanner')) {
            $image = $request->file('imgBanner');
            $fileName = time() . '_' . $image->getClientOriginalName();
            $imagePath = $image->storeAs("albums/{$albumId}", $fileName, 'public');

            AlbumImage::create([
                'album_id' => $albumId,
                'image_url' => $imagePath,
                'caption' => 'Ảnh banner điểm đến',
                'is_deleted' => 'active'
            ]);
        }

        // Lưu galleryImages[]
        $galleryImages = [];
        if ($request->hasFile('galleryImages')) {
            foreach ($request->file('galleryImages') as $file) {
                $fileName = time() . '_' . $file->getClientOriginalName();
                $storedPath = $file->storeAs("albums/{$albumId}", $fileName, 'public');
                $galleryImages[] = $fileName;

                AlbumImage::create([
                    'album_id' => $albumId,
                    'image_url' => "albums/{$albumId}/{$fileName}",
                    'caption' => 'Ảnh trong thư viện',
                    'is_deleted' => 'active',
                ]);
            }
        }

        // Lưu lastImage
        $lastImageName = null;
        if ($request->hasFile('lastImage')) {
            $file = $request->file('lastImage');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $file->storeAs("albums/{$albumId}", $fileName, 'public');
            $lastImageName = $fileName;

            AlbumImage::create([
                'album_id' => $albumId,
                'image_url' => "albums/{$albumId}/{$fileName}",
                'caption' => 'Ảnh kết thúc',
                'is_deleted' => 'active',
            ]);
        }

        // Lưu delicacyImages[]
        $delicacyImageMap = []; // map tên gốc => tên đã lưu
        if ($request->hasFile('delicacyImages')) {
            foreach ($request->file('delicacyImages') as $file) {
                $originalName = $file->getClientOriginalName(); // FE gửi đúng tên
                $fileName = time() . '_' . $originalName;
                $storedPath = $file->storeAs("albums/{$albumId}", $fileName, 'public');

                $delicacyImageMap[$originalName] = $fileName;

                AlbumImage::create([
                    'album_id' => $albumId,
                    'image_url' => $storedPath,
                    'caption' => 'Ảnh món ăn',
                    'is_deleted' => 'active',
                ]);
            }
        }

        // Tạo destination
        $destination = Destination::create([
            'name' => $request->name,
            'album_id' => $albumId,
            'category_id' => $request->category_id,
            'description' => $request->description,
            'area' => $request->area,
            'img_banner' => $imagePath,
            'is_deleted' => 'active',
            'slug' => Str::slug($request->name),
        ]);

        // Gán ảnh vào sections phù hợp
        foreach ($sections as &$section) {
            $type = $section['type'];

            if ($type === 'gallery') {
                $section['content'] = $galleryImages;
            }

            if ($type === 'lastImage' && $lastImageName) {
                $section['content'] = $lastImageName;
            }

            if ($type === 'regionalDelicacies') {
                foreach ($section['content']['dishes'] as &$dish) {
                    $original = $dish['image'];
                    if (isset($delicacyImageMap[$original])) {
                        $dish['image'] = $delicacyImageMap[$original];
                    }
                }
            }

            DestinationSection::create([
                'destination_id' => $destination->destination_id,
                'type' => $type,
                'title' => $section['title'] ?? $this->makeSectionTitle($type),
                'content' => $section['content'] ?? null,
            ]);
        }
        unset($section); // good practice

        $destination = Destination::with('sections')->find($destination->destination_id);
        $destination->img_banner_url = $destination->img_banner ? asset('storage/' . $destination->img_banner) : null;
        $destination->sections = $this->decodeSections($destination->sections);

        return response()->json(['message' => 'Tạo điểm đến thành công', 'destination' => $destination], 201);
    }
    public function update(Request $request, $id)
    {
        $destination = Destination::find($id);
        if (!$destination) {
            return response()->json(['message' => 'Không tìm thấy điểm đến'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'category_id' => 'nullable|numeric',
            'description' => 'nullable|string',
            'area' => 'nullable|string|max:100',
            'imgBanner' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'sections' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Dữ liệu không hợp lệ', 'errors' => $validator->errors()], 422);
        }

        $albumId = $destination->album_id;

        // Nếu có ảnh mới mà chưa có album → tạo
        if (!$albumId && ($request->hasFile('imgBanner') || $request->hasFile('galleryImages') || $request->hasFile('delicacyImages') || $request->hasFile('lastImage'))) {
            $album = Album::create([
                'title' => $destination->name . ' - Album',
                'is_deleted' => 'active',
            ]);
            $albumId = $album->album_id;
            $destination->album_id = $albumId;
        }

        // 1. Xử lý ảnh banner
        if ($request->hasFile('imgBanner')) {
            if ($destination->img_banner && Storage::disk('public')->exists($destination->img_banner)) {
                Storage::disk('public')->delete($destination->img_banner);
            }
            $image = $request->file('imgBanner');
            $fileName = time() . '_' . $image->getClientOriginalName();
            $imagePath = $image->storeAs("albums/{$albumId}", $fileName, 'public');
            $destination->img_banner = $imagePath;

            AlbumImage::create([
                'album_id' => $albumId,
                'image_url' => $imagePath,
                'caption' => 'Ảnh banner điểm đến',
                'is_deleted' => 'active',
            ]);
        }

        // 2. Gallery Images
        $galleryImages = null;
        if ($request->hasFile('galleryImages')) {
            $galleryImages = [];
            foreach ($request->file('galleryImages') as $file) {
                $fileName = time() . '_' . $file->getClientOriginalName();
                $storedPath = $file->storeAs("albums/{$albumId}", $fileName, 'public');
                $galleryImages[] = $fileName;

                AlbumImage::create([
                    'album_id' => $albumId,
                    'image_url' => "albums/{$albumId}/{$fileName}",
                    'caption' => 'Ảnh trong thư viện',
                    'is_deleted' => 'active',
                ]);
            }
        }

        // 3. Last Image
        $lastImageName = null;
        if ($request->hasFile('lastImage')) {
            $file = $request->file('lastImage');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $file->storeAs("albums/{$albumId}", $fileName, 'public');
            $lastImageName = $fileName;

            AlbumImage::create([
                'album_id' => $albumId,
                'image_url' => "albums/{$albumId}/{$fileName}",
                'caption' => 'Ảnh kết thúc',
                'is_deleted' => 'active',
            ]);
        }

        // 4. Ảnh món ăn
        $delicacyImageMap = [];
        if ($request->hasFile('delicacyImages')) {
            foreach ($request->file('delicacyImages') as $file) {
                $originalName = $file->getClientOriginalName();
                $fileName = time() . '_' . $originalName;
                $storedPath = $file->storeAs("albums/{$albumId}", $fileName, 'public');

                $delicacyImageMap[$originalName] = $fileName;

                AlbumImage::create([
                    'album_id' => $albumId,
                    'image_url' => $storedPath,
                    'caption' => 'Ảnh món ăn',
                    'is_deleted' => 'active',
                ]);
            }
        }

        // 5. Cập nhật destination
        $destination->update([
            'name' => $request->name,
            'category_id' => $request->category_id,
            'description' => $request->description,
            'area' => $request->area,
            'slug' => Str::slug($request->name),
            'album_id' => $albumId,
        ]);

        // 6. Xử lý SECTIONS thông minh
        if ($request->has('sections')) {
            $sections = json_decode($request->input('sections'), true);

            if (!is_array($sections)) {
                return response()->json(['message' => 'Sections phải là mảng hợp lệ'], 422);
            }

            $existingSections = $destination->sections()->get()->keyBy('type');
            $incomingTypes = collect($sections)->pluck('type')->toArray();

            foreach ($sections as &$section) {
                $type = $section['type'];
                $title = $section['title'] ?? $this->makeSectionTitle($type);

                // Gắn ảnh nếu có
                if ($type === 'gallery') {
                    $section['content'] = $galleryImages ?: ($existingSections[$type]->content ?? []);
                }

                if ($type === 'lastImage') {
                    $section['content'] = $lastImageName ?: ($existingSections[$type]->content ?? null);
                }

                if ($type === 'regionalDelicacies') {
                    foreach ($section['content']['dishes'] as &$dish) {
                        $original = $dish['image'];
                        if (isset($delicacyImageMap[$original])) {
                            $dish['image'] = $delicacyImageMap[$original];
                        }
                    }
                }

                $newContent = $section['content'];

                // Đã tồn tại section này → so sánh và cập nhật nếu khác
                if (isset($existingSections[$type])) {
                    $oldSection = $existingSections[$type];

                    if (
                        $oldSection->title !== $title ||
                        json_encode($oldSection->content) !== json_encode($newContent)
                    ) {
                        $oldSection->update([
                            'title' => $title,
                            'content' => $newContent,
                        ]);
                    }

                } else {
                    // Section mới → tạo
                    DestinationSection::create([
                        'destination_id' => $destination->destination_id,
                        'type' => $type,
                        'title' => $title,
                        'content' => $newContent,
                    ]);
                }
            }
            unset($section);

            // Xoá section không còn trong mảng mới
            $typesToDelete = $existingSections->keys()->diff($incomingTypes);
            if ($typesToDelete->count()) {
                DestinationSection::where('destination_id', $destination->destination_id)
                    ->whereIn('type', $typesToDelete)
                    ->delete();
            }
        }

        // Reload dữ liệu mới
        $destination = Destination::with('sections')->find($destination->destination_id);
        $destination->img_banner_url = $destination->img_banner ? asset('storage/' . $destination->img_banner) : null;
        $destination->sections = $this->decodeSections($destination->sections);

        return response()->json(['message' => 'Cập nhật điểm đến thành công', 'destination' => $destination]);
    }

    public function softDelete($id)
    {
        $destination = Destination::find($id);
        if (!$destination)
            return response()->json(['message' => 'Không tìm thấy điểm đến'], 404);

        $destination->is_deleted = $destination->is_deleted === 'active' ? 'inactive' : 'active';
        $destination->save();

        return response()->json(['message' => 'Cập nhật trạng thái thành công', 'destination' => $destination]);
    }
    public function destroy($id)
    {
        $destination = Destination::find($id);
        if (!$destination)
            return response()->json(['message' => 'Không tìm thấy điểm đến'], 404);

        $destination->sections()->delete();
        $destination->delete();

        return response()->json(['message' => 'Xóa điểm đến thành công']);
    }
    public function trashed()
    {
        $destinations = Destination::where('is_deleted', 'inactive')->with('sections')->get();

        return response()->json($destinations);
    }
    public function showBySlug($slug)
    {
        $destination = Destination::with(['sections', 'album.images', 'category'])
            ->where('slug', $slug)
            ->where('is_deleted', 'active')
            ->first();

        if (!$destination) {
            return response()->json(['message' => 'Không tìm thấy điểm đến'], 404);
        }

        $destination->img_banner_url = $destination->img_banner ? asset('storage/' . $destination->img_banner) : null;

        if ($destination->album && $destination->album->images) {
            foreach ($destination->album->images as $img) {
                $img->image_url = asset('storage/' . $img->image_url);
            }
        }

        if ($destination->category && $destination->category->thumbnail) {
            $destination->category->thumbnail = asset('storage/' . $destination->category->thumbnail);
        }

        $destination->sections = $this->decodeSections($destination->sections);

        return response()->json($destination);
    }
}