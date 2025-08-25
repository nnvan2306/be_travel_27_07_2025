<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\CustomTour;
use App\Models\Promotion;
use App\Models\Tour;
use App\Models\TourDeparture;
use App\Models\User;
use App\Services\BookingValidationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\Mail\BookingCreated;
use App\Mail\BookingCancelled;
use App\Mail\BookingStatusUpdated;
use App\Models\BusRoute; // Added this import for debugBusRouteData
use Illuminate\Support\Facades\DB; // Added this import for updateBusRouteData
use Illuminate\Support\Str; // Added this import for Str::random()
use Illuminate\Support\Facades\Hash; // Added this import for Hash::make()
use Illuminate\Support\Facades\Log; // Added this import for Log::error()

class BookingController extends Controller
{
    protected $bookingValidationService;

    public function __construct(BookingValidationService $bookingValidationService)
    {
        $this->bookingValidationService = $bookingValidationService;
    }

    // Lấy danh sách booking còn hoạt động với đầy đủ relationships
    public function index()
    {
        $bookings = Booking::with([
            'user',
            'tour.category',
            'tour.destinations',
            'tour.schedules',
            'guide',
            'hotel',
            'busRoute',
            'motorbike',
            'customTour.destination',
            'customTour.user',
            'customTour.destination.category',
            'customTour.destination.album.images',
            'customTour.destination.sections'
        ])->active()->get();

        return response()->json([
            'message' => 'Lấy danh sách booking thành công',
            'total' => $bookings->count(),
            'data' => $bookings
        ]);
    }

    // Lấy chi tiết booking theo ID với đầy đủ relationships
    public function show($id)
    {
        $booking = Booking::with([
            'user',
            'tour.category',
            'tour.destinations',
            'tour.schedules',
            'guide',
            'hotel',
            'busRoute',
            'motorbike',
            'customTour.destination',
            'customTour.user',
            'customTour.destination.category',
            'customTour.destination.album.images',
            'customTour.destination.sections'
        ])->find($id);

        if (!$booking || $booking->is_deleted === 'inactive') {
            return response()->json(['message' => 'Booking không tồn tại'], 404);
        }

        return response()->json([
            'message' => 'Lấy chi tiết booking thành công',
            'data' => $booking
        ]);
    }

    // Lấy danh sách booking của user đang đăng nhập với đầy đủ relationships
    public function myBooking(Request $request)
    {
        $user = $request->user();
        $bookings = Booking::with([
            'user',
            'tour.category',
            'tour.destinations',
            'tour.schedules',
            'guide',
            'hotel',
            'busRoute',
            'motorbike',
            'customTour.destination',
            'customTour.user',
            'customTour.destination.category',
            'customTour.destination.album.images',
            'customTour.destination.sections'
        ])
            ->where('user_id', $user->id)
            ->where('is_deleted', 'active')
            ->get();

        return response()->json([
            'message' => 'Lấy danh sách booking của bạn thành công',
            'total' => $bookings->count(),
            'data' => $bookings
        ]);
    }

    // Lấy tất cả booking (cho admin) với đầy đủ relationships
    public function getAllBookings()
    {
        $bookings = Booking::with([
            'user',
            'tour.category',
            'tour.destinations',
            'tour.schedules',
            'guide',
            'hotel',
            'busRoute',
            'motorbike',
            'customTour.destination',
            'customTour.user',
            'customTour.destination.category',
            'customTour.destination.album.images',
            'customTour.destination.sections'
        ])->get();

        return response()->json([
            'message' => 'Lấy tất cả booking thành công',
            'total' => $bookings->count(),
            'data' => $bookings
        ]);
    }

    // Cập nhật status của booking
    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,confirmed,cancelled,completed'
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Dữ liệu không hợp lệ', 'errors' => $validator->errors()], 422);
        }

        $booking = Booking::with('user')->find($id);
        if (!$booking) {
            return response()->json(['message' => 'Booking không tồn tại'], 404);
        }

        $oldStatus = $booking->status;
        $booking->status = $request->status;
        $booking->save();

        // Gửi email thông báo dựa trên trạng thái mới
        try {
            if ($booking->user && $booking->user->email) {
                if ($request->status === 'cancelled') {
                    // Gửi email hủy đơn hàng
                    Mail::to($booking->user->email)->send(new BookingCancelled($booking, $request->cancel_reason ?? null));
                } else {
                    // Gửi email cập nhật trạng thái
                    Mail::to($booking->user->email)->send(new BookingStatusUpdated($booking, $oldStatus, $booking->status));
                }
            }
        } catch (Exception $e) {
            Log::error('Failed to send booking status email: ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'Cập nhật status booking thành công',
            'data' => [
                'booking_id' => $booking->booking_id,
                'old_status' => $oldStatus,
                'new_status' => $booking->status,
                'updated_at' => $booking->updated_at
            ]
        ]);
    }

    // Tạo booking mới
    public function store(Request $request)
    {
        // Kiểm tra xem có phải là guest booking không
        $isGuestBooking = $request->has('guest_info');
        
        if ($isGuestBooking) {
            // Validation cho guest booking
            $validator = Validator::make($request->all(), [
                'guest_info' => 'required|array',
                'guest_info.name' => 'required|string|max:255',
                'guest_info.email' => 'required|email|max:255',
                'guest_info.phone' => 'required|string|max:20',
                'tour_id' => 'nullable|exists:tours,tour_id',
                'departure_id' => 'nullable|exists:tour_departures,departure_id',
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
                'dataCustom' => 'nullable|array',
                'dataCustom.duration' => 'nullable|string',
                'dataCustom.vehicle' => 'nullable|string',
                'dataCustom.note' => 'nullable|string',
                'dataCustom.destination_id' => 'nullable|exists:destinations,destination_id',
            ]);
        } else {
            // Validation cho user đã đăng nhập
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'tour_id' => 'nullable|exists:tours,tour_id',
                'departure_id' => 'nullable|exists:tour_departures,departure_id',
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
                'dataCustom' => 'nullable|array',
                'dataCustom.duration' => 'nullable|string',
                'dataCustom.vehicle' => 'nullable|string',
                'dataCustom.note' => 'nullable|string',
                'dataCustom.destination_id' => 'nullable|exists:destinations,destination_id',
            ]);
        }

        if ($validator->fails()) {
            return response()->json(['message' => 'Dữ liệu không hợp lệ', 'errors' => $validator->errors()], 422);
        }

        // Xử lý guest booking - tạo user mới
        $userId = null;
        if ($isGuestBooking) {
            try {
                // Kiểm tra email đã tồn tại chưa
                $existingUser = User::where('email', $request->guest_info['email'])->first();
                
                if ($existingUser) {
                    // Nếu email đã tồn tại, sử dụng user đó
                    $userId = $existingUser->id;
                    
                    // Cập nhật thông tin nếu cần
                    $existingUser->update([
                        'full_name' => $request->guest_info['name'],
                        'phone' => $request->guest_info['phone'],
                    ]);
                } else {
                    // Tạo user mới
                    $newUser = User::create([
                        'full_name' => $request->guest_info['name'],
                        'email' => $request->guest_info['email'],
                        'phone' => $request->guest_info['phone'],
                        'password' => Hash::make(Str::random(12)), // Tạo password ngẫu nhiên
                        'role' => 'customer',
                        'is_deleted' => 'active',
                    ]);
                    
                    $userId = $newUser->id;
                }
                
                // Thêm user_id vào request để sử dụng cho booking
                $request->merge(['user_id' => $userId]);
                
            } catch (Exception $e) {
                Log::error('Failed to create guest user: ' . $e->getMessage());
                return response()->json(['message' => 'Không thể tạo tài khoản khách. Vui lòng thử lại!'], 500);
            }
        }

        // Kiểm tra departure nếu có
        if ($request->departure_id) {
            $departure = TourDeparture::find($request->departure_id);
            if (!$departure || $departure->is_deleted === 'inactive') {
                return response()->json(['message' => 'Ngày khởi hành không tồn tại hoặc đã bị xóa'], 404);
            }

            if ($departure->status !== 'available') {
                return response()->json(['message' => 'Ngày khởi hành này không còn khả dụng'], 422);
            }

            if (!$departure->hasAvailableSeats($request->quantity)) {
                return response()->json(['message' => 'Không đủ chỗ cho số lượng khách yêu cầu'], 422);
            }

            // Sử dụng ngày khởi hành từ departure
            $request->merge(['start_date' => $departure->departure_date]);
        }

        // Tính toán ngày kết thúc
        $endDate = $this->bookingValidationService->calculateEndDate(
            $request->start_date,
            $request->tour_id,
            $request->dataCustom['duration'] ?? null
        );

        // Kiểm tra availability của các dịch vụ
        $validationData = [
            'start_date' => $request->start_date,
            'end_date' => $endDate,
            'quantity' => $request->quantity,
            'bus_route_id' => $request->bus_route_id,
            'motorbike_id' => $request->motorbike_id,
        ];

        $validation = $this->bookingValidationService->validateBookingServices($validationData);
        
        if (!$validation['valid']) {
            return response()->json([
                'message' => 'Có lỗi xảy ra khi kiểm tra availability',
                'errors' => $validation['errors']
            ], 422);
        }

        // Kiểm tra xem hướng dẫn viên đã được đặt vào ngày này chưa
        if ($request->guide_id) {
            $requestDate = $request->start_date;

            // Tìm các booking có cùng hướng dẫn viên và ngày
            $existingBookings = Booking::where('guide_id', $request->guide_id)
                ->where('start_date', $requestDate)
                ->where('status', '!=', 'cancelled')  // Không tính các booking đã hủy
                ->where('is_deleted', '=', 'active')  // Chỉ tính các booking đang hoạt động
                ->count();

            if ($existingBookings > 0) {
                return response()->json([
                    'message' => 'Hướng dẫn viên này đã được đặt vào ngày ' . date('d/m/Y', strtotime($requestDate)) . '. Vui lòng chọn hướng dẫn viên khác hoặc ngày khác.'
                ], 422);
            }
        }

        // Xử lý dataCustom nếu có
        $customTourId = null;
        if ($request->has('dataCustom') && !empty($request->dataCustom)) {
            $customTour = CustomTour::create([
                'user_id' => $request->user_id,
                'destination_id' => $request->dataCustom['destination_id'],
                'vehicle' => $request->dataCustom['vehicle'],
                'duration' => $request->dataCustom['duration'],
                'note' => $request->dataCustom['note'] ?? null,
            ]);
            $customTourId = $customTour->custom_tour_id;
        }

        // Chuẩn bị dữ liệu booking
        $bookingData = $request->all();
        if ($customTourId) {
            $bookingData['custom_tour_id'] = $customTourId;
        }
        
        // Thêm end_date và service_quantity
        $bookingData['end_date'] = $endDate;
        $bookingData['service_quantity'] = $request->quantity;

        $booking = Booking::create($bookingData);

        // Cập nhật booked_count cho departure nếu có
        if ($request->departure_id) {
            $departure = TourDeparture::find($request->departure_id);
            if ($departure) {
                $departure->updateBookedCount($request->quantity, 'add');
            }
        }

        // Gửi email xác nhận đặt tour
        try {
            // Tải quan hệ user để có thông tin email
            $booking->load(['user', 'tour', 'guide', 'hotel', 'busRoute', 'motorbike', 'customTour']);

            // Chỉ gửi email khi có thông tin user và email hợp lệ
            if ($booking->user && $booking->user->email) {
                Mail::to($booking->user->email)->send(new BookingCreated($booking));
            }
                } catch (Exception $e) {
            // Log lỗi nhưng không làm gián đoạn tiến trình
            Log::error('Failed to send booking confirmation email: ' . $e->getMessage());
        }

        // Chuẩn bị response data
        $responseData = [
            'message' => 'Đặt tour thành công!',
            'booking_id' => $booking->booking_id,
            'total_price' => $booking->total_price,
            'status' => $booking->status,
        ];

        // Nếu là guest booking, trả về thông tin user mới tạo
        if ($isGuestBooking) {
            $user = User::find($userId);
            $responseData['guest_user'] = [
                'id' => $user->id,
                'name' => $user->full_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'message' => 'Tài khoản đã được tạo tự động với email: ' . $user->email
            ];
        }

        // Nếu chọn VNPay thì trả về link thanh toán
        if ($request->payment_method_id === 1 || $request->payment_method_id === "1") {
            $vnp_Url = 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html';
            $vnp_Returnurl = 'http://dev-test.fstack.io.vn/api/vnpay/return';
            $vnp_TmnCode = 'B76WT5YR';
            $vnp_HashSecret = 'ST178S34C3LKXR630DM8L7FSL6C99K8Y';

            $vnp_TxnRef = $booking->booking_id; // Sử dụng booking_id làm mã đơn hàng
            $vnp_OrderInfo = 'Thanh toán booking VTravel #' . $booking->booking_id;
            $vnp_OrderType = 'other';
            $vnp_Amount = (int) ($booking->total_price * 100); // VNPay yêu cầu x100 và phải là integer
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

            $responseData['payment_url'] = $vnp_Url;
            $responseData['booking'] = $booking->load([
                'user',
                'tour',
                'guide',
                'hotel',
                'busRoute',
                'motorbike',
                'customTour'
            ]);
            
            return response()->json($responseData, 201);
        }

        $responseData['booking'] = $booking->load([
            'user',
            'tour',
            'guide',
            'hotel',
            'busRoute',
            'motorbike',
            'customTour'
        ]);
        
        return response()->json($responseData, 201);
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

                // Gửi email xác nhận thanh toán thành công
                try {
                    $booking->load(['user', 'tour']);
                    if ($booking->user && $booking->user->email) {
                        Mail::to($booking->user->email)->send(new BookingCreated($booking));
                    }
                } catch (Exception $e) {
                    Log::error('Failed to send payment success email: ' . $e->getMessage());
                }

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

                // Gửi email thông báo thanh toán thất bại
                try {
                    $booking->load(['user', 'tour']);
                    if ($booking->user && $booking->user->email) {
                        Mail::to($booking->user->email)
                            ->send(new BookingCancelled($booking, 'Thanh toán không thành công'));
                    }
                } catch (Exception $e) {
                    Log::error('Failed to send payment failed email: ' . $e->getMessage());
                }

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

    /**
     * Kiểm tra xem người dùng đã từng đặt tour cụ thể hay chưa
     *
     * @param int $userId ID của người dùng
     * @param int $tourId ID của tour
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkUserBookedTour($userId, $tourId)
    {
        try {
            // Kiểm tra có booking nào thỏa điều kiện không
            $hasBooked = Booking::where('user_id', $userId)
                ->where('tour_id', $tourId)
                ->where(function ($query) {
                    // Chỉ tính các booking đã thanh toán hoặc hoàn thành
                    $query->whereIn('status', ['paid', 'completed', 'confirmed']);
                })
                ->exists();

            return response()->json([
                'success' => true,
                'hasBooked' => $hasBooked
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Đã có lỗi khi kiểm tra thông tin booking: ' . $e->getMessage(),
                'hasBooked' => false
            ], 500);
        }
    }

    /**
     * Kiểm tra availability của xe khách
     */
    public function checkBusRouteAvailability(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bus_route_id' => 'required|exists:bus_routes,bus_route_id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->bookingValidationService->checkBusRouteAvailability(
            $request->bus_route_id,
            $request->start_date,
            $request->end_date,
            $request->quantity
        );

        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }

    /**
     * Kiểm tra availability của xe máy
     */
    public function checkMotorbikeAvailability(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'motorbike_id' => 'required|exists:motorbikes,motorbike_id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->bookingValidationService->checkMotorbikeAvailability(
            $request->motorbike_id,
            $request->start_date,
            $request->end_date,
            $request->quantity
        );

        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }

    /**
     * Kiểm tra availability của tất cả dịch vụ trong một booking
     */
    public function checkBookingAvailability(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'quantity' => 'required|integer|min:1',
            'bus_route_id' => 'nullable|exists:bus_routes,bus_route_id',
            'motorbike_id' => 'nullable|exists:motorbikes,motorbike_id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $validator->errors()
            ], 422);
        }

        $validationData = $request->only([
            'start_date', 'end_date', 'quantity', 'bus_route_id', 'motorbike_id'
        ]);

        $result = $this->bookingValidationService->validateBookingServices($validationData);

        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }

    /**
     * Áp dụng mã khuyến mãi vào đơn đặt tour
     */
    public function applyPromoCode(Request $request, $bookingId)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|exists:promotions,code',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Mã khuyến mãi không hợp lệ hoặc không tồn tại',
                'errors' => $validator->errors()
            ], 422);
        }

        $booking = Booking::findOrFail($bookingId);
        $promotion = Promotion::where('code', $request->code)->first();

        // Kiểm tra mã khuyến mãi có hợp lệ không
        if (!$promotion->isActive()) {
            $reason = '';
            if (!$promotion->is_active) {
                $reason = 'Mã khuyến mãi đã bị vô hiệu hóa';
            } elseif ($promotion->isExpired()) {
                $reason = 'Mã khuyến mãi đã hết hạn';
            } elseif ($promotion->max_uses !== null && $promotion->current_uses >= $promotion->max_uses) {
                $reason = 'Mã khuyến mãi đã được sử dụng hết';
            }

            return response()->json([
                'success' => false,
                'message' => $reason,
            ], 400);
        }

        // Tính toán số tiền giảm giá
        $totalAmount = $booking->total_amount;
        $discountAmount = $promotion->calculateDiscount($totalAmount);
        $finalAmount = $totalAmount - $discountAmount;

        // Cập nhật booking
        $booking->update([
            'id' => $promotion->id,
            'discount_amount' => $discountAmount,
            'final_amount' => $finalAmount
        ]);

        // Tăng số lần sử dụng mã
        $promotion->incrementUsage();

        return response()->json([
            'success' => true,
            'data' => [
                'booking' => $booking,
                'promotion' => $promotion,
                'discount_amount' => $discountAmount,
                'final_amount' => $finalAmount
            ],
            'message' => 'Áp dụng mã khuyến mãi thành công!'
        ]);
    }

    /**
     * Debug endpoint để kiểm tra dữ liệu xe khách và booking
     */
    public function debugBusRouteData(Request $request)
    {
        $busRouteId = $request->input('bus_route_id');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        if (!$busRouteId || !$startDate || !$endDate) {
            return response()->json([
                'success' => false,
                'message' => 'Thiếu thông tin bus_route_id, start_date, end_date'
            ], 400);
        }

        // Lấy thông tin xe khách
        $busRoute = BusRoute::find($busRouteId);
        if (!$busRoute) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy xe khách'
            ], 404);
        }

        // Lấy các booking trong khoảng thời gian
        $bookings = Booking::where('bus_route_id', $busRouteId)
            ->where('is_deleted', 'active')
            ->whereIn('status', ['pending', 'confirmed'])
            ->where(function ($q) use ($startDate, $endDate) {
                $q->where(function ($subQ) use ($startDate, $endDate) {
                    $subQ->where('start_date', '>=', $startDate)
                         ->where('start_date', '<=', $endDate);
                })->orWhere(function ($subQ) use ($startDate, $endDate) {
                    $subQ->where('end_date', '>=', $startDate)
                         ->where('end_date', '<=', $endDate);
                })->orWhere(function ($subQ) use ($startDate, $endDate) {
                    $subQ->where('start_date', '<=', $startDate)
                         ->where('end_date', '>=', $endDate);
                });
            })
            ->get();

        $totalBookedSeats = $bookings->sum('quantity');
        $availableSeats = $busRoute->total_seats - $totalBookedSeats;

        return response()->json([
            'success' => true,
            'data' => [
                'bus_route' => [
                    'id' => $busRoute->bus_route_id,
                    'name' => $busRoute->route_name,
                    'total_seats' => $busRoute->total_seats,
                    'seats' => $busRoute->seats,
                    'is_deleted' => $busRoute->is_deleted
                ],
                'bookings' => $bookings->map(function ($booking) {
                    return [
                        'id' => $booking->booking_id,
                        'quantity' => $booking->quantity,
                        'service_quantity' => $booking->service_quantity,
                        'start_date' => $booking->start_date,
                        'end_date' => $booking->end_date,
                        'status' => $booking->status,
                        'is_deleted' => $booking->is_deleted
                    ];
                }),
                'calculation' => [
                    'total_booked_seats' => $totalBookedSeats,
                    'available_seats' => $availableSeats,
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ]
            ]
        ]);
    }

    /**
     * Cập nhật dữ liệu xe khách
     */
    public function updateBusRouteData()
    {
        try {
            // Cập nhật bus_routes với total_seats
            DB::table('bus_routes')->update([
                'total_seats' => DB::raw('CASE 
                    WHEN total_seats > 0 THEN total_seats 
                    WHEN seats > 0 THEN seats 
                    ELSE 45 
                END'),
            ]);

            // Lấy danh sách xe khách để kiểm tra
            $busRoutes = BusRoute::select('bus_route_id', 'route_name', 'seats', 'total_seats', 'is_deleted')->get();

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật dữ liệu xe khách thành công',
                'data' => $busRoutes
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi cập nhật dữ liệu: ' . $e->getMessage()
            ], 500);
        }
    }
}
