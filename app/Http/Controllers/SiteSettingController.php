<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use Illuminate\Http\Request;

class SiteSettingController extends Controller
{
    // Danh sách settings (chỉ active)
    public function index(Request $request)
    {
        try {
            $query = SiteSetting::active();

            // Tìm kiếm theo key_name hoặc description
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('key_name', 'like', '%' . $search . '%')
                      ->orWhere('description', 'like', '%' . $search . '%');
                });
            }

            // Lọc theo key_name cụ thể
            if ($request->has('key_name')) {
                $query->where('key_name', $request->key_name);
            }

            // Sắp xếp
            $sortBy = $request->get('sort_by', 'key_name');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);

            $settings = $query->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $settings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy danh sách settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Chi tiết setting
    public function show($id)
    {
        try {
            $setting = SiteSetting::find($id);

            if (!$setting) {
                return response()->json([
                    'success' => false,
                    'message' => 'Setting không tồn tại'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $setting
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy chi tiết setting',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Tạo setting mới
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'key_name' => 'required|string|max:100|unique:site_settings,key_name',
                'key_value' => 'nullable|string',
                'description' => 'nullable|string'
            ], [
                'key_name.required' => 'Tên key là bắt buộc',
                'key_name.unique' => 'Tên key đã tồn tại',
                'key_name.max' => 'Tên key không được vượt quá 100 ký tự'
            ]);

            $validated['is_deleted'] = 'active';
            $validated['updated_at'] = now();

            $setting = SiteSetting::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Tạo setting thành công',
                'data' => $setting
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tạo setting',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Cập nhật setting
    public function update(Request $request, $id)
    {
        try {
            $setting = SiteSetting::find($id);

            if (!$setting) {
                return response()->json([
                    'success' => false,
                    'message' => 'Setting không tồn tại'
                ], 404);
            }

            $validated = $request->validate([
                'key_name' => 'required|string|max:100|unique:site_settings,key_name,' . $id . ',setting_id',
                'key_value' => 'nullable|string',
                'description' => 'nullable|string'
            ], [
                'key_name.required' => 'Tên key là bắt buộc',
                'key_name.unique' => 'Tên key đã tồn tại',
                'key_name.max' => 'Tên key không được vượt quá 100 ký tự'
            ]);

            $validated['updated_at'] = now();
            $setting->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật setting thành công',
                'data' => $setting->fresh()
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi cập nhật setting',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Soft delete (ẩn/hiện setting)
    public function softDelete($id)
    {
        try {
            $setting = SiteSetting::find($id);

            if (!$setting) {
                return response()->json([
                    'success' => false,
                    'message' => 'Setting không tồn tại'
                ], 404);
            }

            $newStatus = $setting->is_deleted === 'active' ? 'inactive' : 'active';
            $setting->update([
                'is_deleted' => $newStatus,
                'updated_at' => now()
            ]);

            $action = $newStatus === 'inactive' ? 'ẩn' : 'hiện';

            return response()->json([
                'success' => true,
                'message' => "Đã {$action} setting thành công",
                'data' => $setting
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi cập nhật trạng thái setting',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Xóa vĩnh viễn
    public function destroy($id)
    {
        try {
            $setting = SiteSetting::find($id);

            if (!$setting) {
                return response()->json([
                    'success' => false,
                    'message' => 'Setting không tồn tại'
                ], 404);
            }

            $setting->delete();

            return response()->json([
                'success' => true,
                'message' => 'Xóa setting thành công'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xóa setting',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Danh sách settings đã ẩn
    public function trashed(Request $request)
    {
        try {
            $settings = SiteSetting::inactive()
                ->orderBy('key_name')
                ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $settings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy danh sách settings đã ẩn',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Thống kê settings
    public function statistics()
    {
        try {
            $stats = [
                'total' => SiteSetting::count(),
                'active' => SiteSetting::active()->count(),
                'inactive' => SiteSetting::inactive()->count(),
                'recent_updated' => SiteSetting::active()
                    ->where('updated_at', '>=', now()->subDays(7))
                    ->count()
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy thống kê settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Lấy setting theo key_name
    public function getByKey($keyName)
    {
        try {
            $setting = SiteSetting::active()->where('key_name', $keyName)->first();

            if (!$setting) {
                return response()->json([
                    'success' => false,
                    'message' => 'Setting không tồn tại'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $setting
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy setting',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Cập nhật nhiều settings cùng lúc
    public function bulkUpdate(Request $request)
    {
        try {
            $validated = $request->validate([
                'settings' => 'required|array',
                'settings.*.key_name' => 'required|string|max:100',
                'settings.*.key_value' => 'nullable|string',
                'settings.*.description' => 'nullable|string'
            ]);

            $updatedSettings = [];

            foreach ($validated['settings'] as $settingData) {
                $setting = SiteSetting::setValue(
                    $settingData['key_name'],
                    $settingData['key_value'] ?? null,
                    $settingData['description'] ?? null
                );
                $updatedSettings[] = $setting;
            }

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật settings thành công',
                'data' => $updatedSettings
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi cập nhật settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}