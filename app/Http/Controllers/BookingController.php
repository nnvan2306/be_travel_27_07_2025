<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BookingController extends Controller
{
    // Lấy danh sách booking còn hoạt động
    public function index()
    {
        $bookings = Booking::with([
            'user',
            'tour',
            'guide',
            'hotel',
            'busRoute',
            'motorbike',
            'customTour'
        ])->active()->get();

        return response()->json($bookings);
    }

    // Lấy chi tiết booking theo ID
    public function show($id)
    {
        $booking = Booking::with([
            'user',
            'tour',
            'guide',
            'hotel',
            'busRoute',
            'motorbike',
            'customTour'
        ])->find($id);

        if (!$booking || $booking->is_deleted === 'inactive') {
            return response()->json(['message' => 'Booking không tồn tại'], 404);
        }

        return response()->json($booking);
    }

    // Tạo booking mới
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'tour_id' => 'nullable|exists:tours,tour_id',
            'custom_tour_id' => 'nullable|exists:custom_tours,custom_tour_id',
            'guide_id' => 'nullable|exists:guides,guide_id',
            'hotel_id' => 'nullable|exists:hotels,hotel_id',
            'bus_route_id' => 'nullable|exists:bus_routes,bus_route_id',
            'motorbike_id' => 'nullable|exists:motorbikes,motorbike_id',
            'quantity' => 'required|integer|min:1',
            'start_date' => 'required|date',
            'total_price' => 'required|numeric|min:0',
            'payment_method_id' => 'nullable|in:COD,bank_transfer,VNPay,MoMo',
            'status' => 'nullable|in:pending,confirmed,cancelled,completed',
            'cancel_reason' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Dữ liệu không hợp lệ', 'errors' => $validator->errors()], 422);
        }

        $booking = Booking::create($request->all());

        // Nếu chọn VNPay thì trả về link thanh toán
        if ($request->payment_method === 'VNPay') {
            $vnp_Url = config('services.vnpay.url');
            $vnp_Returnurl = config('services.vnpay.return_url');
            $vnp_TmnCode = config('services.vnpay.tmncode');
            $vnp_HashSecret = config('services.vnpay.hash_secret');

            $vnp_TxnRef = $booking->booking_id; // Sử dụng booking_id làm mã đơn hàng
            $vnp_OrderInfo = 'Thanh toán booking VTravel #' . $booking->booking_id;
            $vnp_OrderType = 'other';
            $vnp_Amount = $booking->total_price * 100; // VNPay yêu cầu x100
            $vnp_Locale = 'vn';
            $vnp_BankCode = $request->bank_code ?? '';
            $vnp_IpAddr = $request->ip();

            $inputData = array(
                "vnp_Version" => "2.1.0",
                "vnp_TmnCode" => $vnp_TmnCode,
                "vnp_Amount" => $vnp_Amount,
                "vnp_Command" => "pay",
                "vnp_CreateDate" => date('YmdHis'),
                "vnp_CurrCode" => "VND",
                "vnp_IpAddr" => $vnp_IpAddr,
                "vnp_Locale" => $vnp_Locale,
                "vnp_OrderInfo" => $vnp_OrderInfo,
                "vnp_OrderType" => $vnp_OrderType,
                "vnp_ReturnUrl" => $vnp_Returnurl,
                "vnp_TxnRef" => $vnp_TxnRef,
            );
            if (!empty($vnp_BankCode)) {
                $inputData['vnp_BankCode'] = $vnp_BankCode;
            }
            ksort($inputData);
            $query = [];
            foreach ($inputData as $key => $value) {
                $query[] = urlencode($key) . "=" . urlencode($value);
            }
            $hashdata = urldecode(http_build_query($inputData));
            $vnp_SecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);
            $vnp_Url .= "?" . implode('&', $query) . '&vnp_SecureHash=' . $vnp_SecureHash;

            return response()->json([
                'message' => 'Tạo booking thành công, chuyển sang thanh toán VNPay',
                'payment_url' => $vnp_Url,
                'booking' => $booking->load([
                    'user',
                    'tour',
                    'guide',
                    'hotel',
                    'busRoute',
                    'motorbike',
                    'customTour'
                ])
            ], 201);
        }

        return response()->json([
            'message' => 'Tạo booking thành công',
            'booking' => $booking->load([
                'user',
                'tour',
                'guide',
                'hotel',
                'busRoute',
                'motorbike',
                'customTour'
            ])
        ], 201);
    }

    // Cập nhật booking
    public function update(Request $request, $id)
    {
        $booking = Booking::find($id);

        if (!$booking || $booking->is_deleted === 'inactive') {
            return response()->json(['message' => 'Booking không tồn tại'], 404);
        }

        $validator = Validator::make($request->all(), [
            'user_id' => 'sometimes|required|exists:users,id',
            'tour_id' => 'nullable|exists:tours,tour_id',
            'custom_tour_id' => 'nullable|exists:custom_tours,custom_tour_id',
            'guide_id' => 'nullable|exists:guides,guide_id',
            'hotel_id' => 'nullable|exists:hotels,hotel_id',
            'bus_route_id' => 'nullable|exists:bus_routes,bus_route_id',
            'motorbike_id' => 'nullable|exists:motorbikes,motorbike_id',
            'quantity' => 'nullable|integer|min:1',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'total_price' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|in:COD,bank_transfer,VNPay,MoMo',
            'status' => 'nullable|in:pending,confirmed,cancelled,completed',
            'cancel_reason' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Dữ liệu không hợp lệ', 'errors' => $validator->errors()], 422);
        }

        $booking->update($request->all());

        return response()->json([
            'message' => 'Cập nhật booking thành công',
            'booking' => $booking->load([
                'user',
                'tour',
                'guide',
                'hotel',
                'busRoute',
                'motorbike',
                'customTour'
            ])
        ]);
    }

    // Xoá mềm
    public function softDelete($id)
    {
        $booking = Booking::find($id);

        if (!$booking) {
            return response()->json(['message' => 'Booking không tồn tại'], 404);
        }

        $booking->is_deleted = $booking->is_deleted === 'active' ? 'inactive' : 'active';
        $booking->save();

        return response()->json(['message' => 'Cập nhật trạng thái booking thành công', 'booking' => $booking]);
    }

    // Xoá vĩnh viễn
    public function destroy($id)
    {
        $booking = Booking::find($id);

        if (!$booking) {
            return response()->json(['message' => 'Booking không tồn tại'], 404);
        }

        $booking->delete();

        return response()->json(['message' => 'Xoá booking thành công']);
    }

    // Danh sách booking đã bị xoá mềm
    public function trashed()
    {
        $trashed = Booking::with([
            'user',
            'tour',
            'guide',
            'hotel',
            'busRoute',
            'motorbike',
            'customTour'
        ])->inactive()->get();

        return response()->json($trashed);
    }
}