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

    // Lấy danh sách booking của user đang đăng nhập
    public function myBooking(Request $request)
    {
        $user = $request->user();
        $bookings = Booking::with([
            'user', 'tour', 'guide', 'hotel', 'busRoute', 'motorbike', 'customTour'
        ])
        ->where('user_id', $user->id)
        ->where('is_deleted', 'active')
        ->get();

        return response()->json($bookings);
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
            'status' => 'nullable|in:pending,confirmed,cancelled,completed',
            'cancel_reason' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Dữ liệu không hợp lệ', 'errors' => $validator->errors()], 422);
        }

        $booking = Booking::create($request->all());

        // Nếu chọn VNPay thì trả về link thanh toán
        if ($request->payment_method_id === 1 || $request->payment_method_id === "1") {
            $vnp_Url = 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html';
            $vnp_Returnurl = 'http://dev-test.fstack.io.vn/api/vnpay/return';
            $vnp_TmnCode = 'B76WT5YR';
            $vnp_HashSecret = 'ST178S34C3LKXR630DM8L7FSL6C99K8Y';

            $vnp_TxnRef = $booking->booking_id; // Sử dụng booking_id làm mã đơn hàng
            $vnp_OrderInfo = 'Thanh toán booking VTravel #' . $booking->booking_id;
            $vnp_OrderType = 'other';
            $vnp_Amount = (int)($booking->total_price * 100); // VNPay yêu cầu x100 và phải là integer
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
            $hashdata = http_build_query($inputData);
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
        $bookings = Booking::with([
            'user',
            'tour',
            'guide',
            'hotel',
            'busRoute',
            'motorbike',
            'customTour'
        ])->inactive()->get();

        return response()->json($bookings);
    }

    // Xử lý callback từ VNPay
    public function vnpayReturn(Request $request)
    {
        $vnp_HashSecret = 'ST178S34C3LKXR630DM8L7FSL6C99K8Y';
        $inputData = $request->all();
        $vnp_SecureHash = $inputData['vnp_SecureHash'] ?? '';
        
        // Loại bỏ vnp_SecureHash và vnp_SecureHashType khỏi inputData
        unset($inputData['vnp_SecureHash']);
        unset($inputData['vnp_SecureHashType']);
        
        // Sắp xếp dữ liệu theo key
        ksort($inputData);
        
        // Tạo chuỗi hash data
        $hashData = http_build_query($inputData);
        $secureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);

        // Kiểm tra chữ ký
        if ($secureHash == $vnp_SecureHash) {

            // Lấy thông tin giao dịch
            $vnp_ResponseCode = $request->vnp_ResponseCode;
            $vnp_TransactionStatus = $request->vnp_TransactionStatus;
            $vnp_TxnRef = $request->vnp_TxnRef; // Đây là booking_id
            $vnp_Amount = $request->vnp_Amount;
            $vnp_BankTranNo = $request->vnp_BankTranNo;
            $vnp_TransactionNo = $request->vnp_TransactionNo;
            $vnp_PayDate = $request->vnp_PayDate;

            // Tìm booking theo booking_id với relationship
            $booking = Booking::with(['tour', 'user'])->find($vnp_TxnRef);
            
            if (!$booking) {
                // Chuyển hướng về frontend với thông báo lỗi
                return redirect()->away(config('app.frontend_url', 'http://localhost:3000') . '/booking-success?' . http_build_query([
                    'status' => 'error',
                    'message' => 'Không tìm thấy booking',
                    'booking_id' => $vnp_TxnRef
                ]));
            }

            // Kiểm tra trạng thái giao dịch
            if ($vnp_ResponseCode == '00' && $vnp_TransactionStatus == '00') {
                // Thanh toán thành công
                $booking->update([
                    'status' => 'confirmed',
                    'payment_status' => 'paid',
                    'payment_date' => date('Y-m-d H:i:s', strtotime($vnp_PayDate)),
                    'transaction_id' => $vnp_TransactionNo,
                    'bank_transaction_no' => $vnp_BankTranNo
                ]);

                // Chuyển hướng về frontend với thông tin thành công
                return redirect()->away(config('app.frontend_url', 'http://localhost:3000') . '/booking-success?' . http_build_query([
                    'status' => 'success',
                    'message' => 'Thanh toán thành công!',
                    'booking_id' => $booking->booking_id,
                    'transaction_no' => $vnp_TransactionNo,
                    'amount' => $vnp_Amount / 100, // Chia lại 100 để hiển thị đúng
                    'payment_date' => date('Y-m-d H:i:s', strtotime($vnp_PayDate)),
                    'tour_name' => $booking->tour ? $booking->tour->name : 'N/A',
                    'customer_name' => $booking->user ? $booking->user->name : 'N/A'
                ]));
            } else {
                // Thanh toán thất bại
                $booking->update([
                    'status' => 'cancelled',
                    'payment_status' => 'failed'
                ]);

                // Chuyển hướng về frontend với thông tin thất bại
                return redirect()->away(config('app.frontend_url', 'http://localhost:3000') . '/booking-success?' . http_build_query([
                    'status' => 'failed',
                    'message' => 'Thanh toán thất bại!',
                    'booking_id' => $booking->booking_id,
                    'response_code' => $vnp_ResponseCode,
                    'transaction_status' => $vnp_TransactionStatus,
                    'tour_name' => $booking->tour ? $booking->tour->name : 'N/A',
                    'customer_name' => $booking->user ? $booking->user->name : 'N/A'
                ]));
            }
        } else {
            // Chữ ký không hợp lệ - chuyển hướng về frontend
            return redirect()->away(config('app.frontend_url', 'http://localhost:3000') . '/booking-success?' . http_build_query([
                'status' => 'invalid_signature',
                'message' => 'Chữ ký không hợp lệ!'
            ]));
        }
    }
}