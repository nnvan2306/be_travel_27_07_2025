<?php

namespace App\Http\Controllers;

use App\Models\TourDeparture;
use App\Models\Tour;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TourDepartureController extends Controller
{
    /**
     * Lấy danh sách các ngày khởi hành của một tour
     */
    public function index(Request $request)
    {
        $query = TourDeparture::with(['tour']);

        // Lọc theo tour_id nếu có
        if ($request->has('tour_id')) {
            $tourId = $request->get('tour_id');
            if (!Tour::where('tour_id', $tourId)->where('is_deleted', 'active')->exists()) {
                return response()->json(['message' => 'Tour không tồn tại hoặc đã bị xóa'], 404);
            }
            $query->where('tour_id', $tourId);
        }

        // Lọc theo tháng và năm nếu có
        if ($request->has('month') && $request->has('year')) {
            $query->byMonthYear($request->get('month'), $request->get('year'));
        }

        // Lọc theo khoảng thời gian nếu có
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->byDateRange($request->get('start_date'), $request->get('end_date'));
        }

        // Chỉ lấy các departure trong tương lai
        if ($request->get('future_only', true)) {
            $query->future();
        }

        // Lọc theo trạng thái
        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        // Sắp xếp theo ngày khởi hành
        $query->orderBy('departure_date', 'asc');

        $departures = $query->active()->get();

        return response()->json($departures);
    }

    /**
     * Lấy thông tin chi tiết một ngày khởi hành
     */
    public function show($id)
    {
        $departure = TourDeparture::with(['tour', 'bookings'])->find($id);

        if (!$departure) {
            return response()->json(['message' => 'Không tìm thấy ngày khởi hành'], 404);
        }

        if ($departure->is_deleted === 'inactive') {
            return response()->json(['message' => 'Ngày khởi hành đã bị xóa'], 404);
        }

        return response()->json($departure);
    }

    /**
     * Tạo mới ngày khởi hành
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tour_id' => 'required|exists:tours,tour_id',
            'departure_date' => 'required|date|after:today',
            'price' => 'required|numeric|min:0',
            'max_capacity' => 'nullable|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Dữ liệu không hợp lệ', 'errors' => $validator->errors()], 422);
        }

        // Kiểm tra tour có active không
        if (!Tour::where('tour_id', $request->tour_id)->where('is_deleted', 'active')->exists()) {
            return response()->json(['message' => 'Tour không tồn tại hoặc đã bị xóa'], 404);
        }

        // Kiểm tra ngày khởi hành đã tồn tại chưa
        $existingDeparture = TourDeparture::where('tour_id', $request->tour_id)
            ->where('departure_date', $request->departure_date)
            ->where('is_deleted', 'active')
            ->first();

        if ($existingDeparture) {
            return response()->json(['message' => 'Ngày khởi hành này đã tồn tại cho tour này'], 422);
        }

        DB::beginTransaction();
        try {
            $departure = TourDeparture::create([
                'tour_id' => $request->tour_id,
                'departure_date' => $request->departure_date,
                'price' => $request->price,
                'max_capacity' => $request->max_capacity ?? 50,
                'booked_count' => 0,
                'status' => 'available',
                'notes' => $request->notes,
                'is_deleted' => 'active',
            ]);

            DB::commit();
            return response()->json($departure->load('tour'), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Lỗi khi tạo ngày khởi hành: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Cập nhật ngày khởi hành
     */
    public function update(Request $request, $id)
    {
        $departure = TourDeparture::find($id);
        if (!$departure) {
            return response()->json(['message' => 'Không tìm thấy ngày khởi hành'], 404);
        }

        if ($departure->is_deleted === 'inactive') {
            return response()->json(['message' => 'Ngày khởi hành đã bị xóa'], 404);
        }

        $validator = Validator::make($request->all(), [
            'departure_date' => 'sometimes|date|after:today',
            'price' => 'sometimes|numeric|min:0',
            'max_capacity' => 'sometimes|integer|min:1',
            'status' => 'sometimes|in:available,full,cancelled',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Dữ liệu không hợp lệ', 'errors' => $validator->errors()], 422);
        }

        // Kiểm tra ngày khởi hành mới đã tồn tại chưa (nếu có thay đổi)
        if ($request->has('departure_date') && $request->departure_date !== $departure->departure_date) {
            $existingDeparture = TourDeparture::where('tour_id', $departure->tour_id)
                ->where('departure_date', $request->departure_date)
                ->where('departure_id', '!=', $id)
                ->where('is_deleted', 'active')
                ->first();

            if ($existingDeparture) {
                return response()->json(['message' => 'Ngày khởi hành này đã tồn tại cho tour này'], 422);
            }
        }

        DB::beginTransaction();
        try {
            $departure->update($request->only([
                'departure_date',
                'price',
                'max_capacity',
                'status',
                'notes',
            ]));

            DB::commit();
            return response()->json($departure->load('tour'));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Lỗi khi cập nhật ngày khởi hành: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Xóa ngày khởi hành (soft delete)
     */
    public function destroy($id)
    {
        $departure = TourDeparture::find($id);
        if (!$departure) {
            return response()->json(['message' => 'Không tìm thấy ngày khởi hành'], 404);
        }

        if ($departure->is_deleted === 'inactive') {
            return response()->json(['message' => 'Ngày khởi hành đã bị xóa'], 404);
        }

        // Kiểm tra xem có booking nào đã đặt cho departure này không
        if ($departure->booked_count > 0) {
            return response()->json(['message' => 'Không thể xóa ngày khởi hành đã có khách đặt'], 422);
        }

        DB::beginTransaction();
        try {
            $departure->update(['is_deleted' => 'inactive']);
            DB::commit();
            return response()->json(['message' => 'Đã xóa ngày khởi hành']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Lỗi khi xóa ngày khởi hành: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Lấy danh sách các tháng có departure cho một tour
     */
    public function getAvailableMonths(Request $request)
    {
        $tourId = $request->get('tour_id');
        if (!$tourId) {
            return response()->json(['message' => 'Tour ID là bắt buộc'], 422);
        }

        if (!Tour::where('tour_id', $tourId)->where('is_deleted', 'active')->exists()) {
            return response()->json(['message' => 'Tour không tồn tại hoặc đã bị xóa'], 404);
        }

        $months = TourDeparture::where('tour_id', $tourId)
            ->where('is_deleted', 'active')
            ->where('departure_date', '>=', Carbon::today())
            ->selectRaw('YEAR(departure_date) as year, MONTH(departure_date) as month')
            ->distinct()
            ->orderBy('year', 'asc')
            ->orderBy('month', 'asc')
            ->get()
            ->map(function ($item) {
                return $item->month . '/' . $item->year;
            });

        return response()->json([
            'success' => true,
            'data' => $months
        ]);
    }

    /**
     * Lấy danh sách departures theo tour với filter tháng/năm
     */
    public function getByTour(Request $request, $tourId)
    {
        if (!Tour::where('tour_id', $tourId)->where('is_deleted', 'active')->exists()) {
            return response()->json(['message' => 'Tour không tồn tại hoặc đã bị xóa'], 404);
        }

        $query = TourDeparture::where('tour_id', $tourId)
            ->where('is_deleted', 'active');

        // Lọc theo tháng và năm nếu có
        if ($request->has('month') && $request->has('year')) {
            $month = $request->get('month');
            $year = $request->get('year');
            $query->whereRaw('MONTH(departure_date) = ? AND YEAR(departure_date) = ?', [$month, $year]);
        }

        // Chỉ lấy các departure trong tương lai
        $query->where('departure_date', '>=', Carbon::today());

        // Sắp xếp theo ngày khởi hành
        $departures = $query->orderBy('departure_date', 'asc')->get();

        return response()->json([
            'success' => true,
            'data' => $departures
        ]);
    }

    /**
     * Lấy thống kê departure cho một tour
     */
    public function getStats(Request $request)
    {
        $tourId = $request->get('tour_id');
        if (!$tourId) {
            return response()->json(['message' => 'Tour ID là bắt buộc'], 422);
        }

        $stats = TourDeparture::where('tour_id', $tourId)
            ->where('is_deleted', 'active')
            ->selectRaw('
                COUNT(*) as total_departures,
                COUNT(CASE WHEN status = "available" THEN 1 END) as available_departures,
                COUNT(CASE WHEN status = "full" THEN 1 END) as full_departures,
                COUNT(CASE WHEN status = "cancelled" THEN 1 END) as cancelled_departures,
                SUM(booked_count) as total_booked,
                SUM(max_capacity) as total_capacity
            ')
            ->first();

        return response()->json($stats);
    }
}
