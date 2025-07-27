<?php

namespace App\Http\Controllers;

use App\Models\TourSchedule;
use App\Models\Tour;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class TourScheduleController extends Controller
{
    // Lấy danh sách lịch trình, có thể kèm lọc theo tour_id
    public function index(Request $request)
    {
        $query = TourSchedule::with(['destination']);

        // Kiểm tra xem có truyền tour_id trong request không
        if ($request->has('tour_id')) {
            $tourId = $request->get('tour_id');
            // Đảm bảo tour tồn tại và đang active
            if (!Tour::where('tour_id', $tourId)->where('is_deleted', 'active')->exists()) {
                return response()->json(['message' => 'Tour không tồn tại hoặc đã bị xóa'], 404);
            }
            $query->where('tour_id', $tourId);
        }

        return response()->json($query->get());
    }

    // Tạo mới lịch trình tour
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tour_id' => 'required|exists:tours,tour_id',
            'day' => 'required|integer|min:1',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after_or_equal:start_time',
            'title' => 'required|string|max:255',
            'activity_description' => 'nullable|string',
            'destination_id' => 'nullable|exists:destinations,destination_id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Kiểm tra tour có active không
        if (!Tour::where('tour_id', $request->tour_id)->where('is_deleted', 'active')->exists()) {
            return response()->json(['message' => 'Tour không tồn tại hoặc đã bị xóa'], 404);
        }

        DB::beginTransaction();
        try {
            $schedule = TourSchedule::create([
                'tour_id' => $request->tour_id,
                'day' => $request->day,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'title' => $request->title,
                'activity_description' => $request->activity_description,
                'destination_id' => $request->destination_id,
            ]);

            DB::commit();
            return response()->json($schedule->load('destination'), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Lỗi khi tạo lịch trình: ' . $e->getMessage()], 500);
        }
    }

    // Xem chi tiết một lịch trình
    public function show($id)
    {
        $schedule = TourSchedule::with(['destination'])->find($id);

        if (!$schedule) {
            return response()->json(['message' => 'Không tìm thấy lịch trình'], 404);
        }

        // Kiểm tra tour có active không
        if (!Tour::where('tour_id', $schedule->tour_id)->where('is_deleted', 'active')->exists()) {
            return response()->json(['message' => 'Tour liên kết đã bị xóa'], 404);
        }

        return response()->json($schedule);
    }

    // Cập nhật lịch trình
    public function update(Request $request, $id)
    {
        $schedule = TourSchedule::find($id);
        if (!$schedule) {
            return response()->json(['message' => 'Không tìm thấy lịch trình'], 404);
        }

        // Kiểm tra tour có active không
        if (!Tour::where('tour_id', $schedule->tour_id)->where('is_deleted', 'active')->exists()) {
            return response()->json(['message' => 'Tour liên kết đã bị xóa'], 404);
        }

        $validator = Validator::make($request->all(), [
            'tour_id' => 'sometimes|exists:tours,tour_id',
            'day' => 'sometimes|integer|min:1',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after_or_equal:start_time',
            'title' => 'sometimes|string|max:255',
            'activity_description' => 'nullable|string',
            'destination_id' => 'nullable|exists:destinations,destination_id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            // Kiểm tra tour_id mới (nếu có) có active không
            if ($request->has('tour_id') && !Tour::where('tour_id', $request->tour_id)->where('is_deleted', 'active')->exists()) {
                return response()->json(['message' => 'Tour mới không tồn tại hoặc đã bị xóa'], 404);
            }

            $schedule->update($request->only([
                'tour_id',
                'day',
                'start_time',
                'end_time',
                'title',
                'activity_description',
                'destination_id',
            ]));

            DB::commit();
            return response()->json($schedule->load('destination'));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Lỗi khi cập nhật lịch trình: ' . $e->getMessage()], 500);
        }
    }

    // Xóa lịch trình
    public function destroy($id)
    {
        $schedule = TourSchedule::find($id);
        if (!$schedule) {
            return response()->json(['message' => 'Không tìm thấy lịch trình'], 404);
        }

        // Kiểm tra tour có active không
        if (!Tour::where('tour_id', $schedule->tour_id)->where('is_deleted', 'active')->exists()) {
            return response()->json(['message' => 'Tour liên kết đã bị xóa'], 404);
        }

        DB::beginTransaction();
        try {
            $schedule->delete();
            DB::commit();
            return response()->json(['message' => 'Đã xóa lịch trình']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Lỗi khi xóa lịch trình: ' . $e->getMessage()], 500);
        }
    }
}