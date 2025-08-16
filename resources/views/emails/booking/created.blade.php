<!DOCTYPE html>
<html>

<head>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .header {
            background: linear-gradient(to right, #4a90e2, #8e44ad);
            color: white;
            padding: 15px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }

        .content {
            padding: 20px;
        }

        .booking-details {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }

        .footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 12px;
            color: #777;
        }

        .btn {
            display: inline-block;
            background-color: #4a90e2;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 15px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h2>Xác Nhận Đặt Tour Thành Công</h2>
        </div>

        <div class="content">
            <p>Xin chào <strong>{{ $booking->user->name }}</strong>,</p>

            <p>Cảm ơn bạn đã đặt tour của VTravel. Chúng tôi xác nhận đã nhận được đơn đặt tour của bạn.</p>

            <div class="booking-details">
                <h3>Thông tin đặt tour:</h3>
                <p><strong>Mã đơn hàng:</strong> #{{ $booking->booking_id }}</p>
                <p><strong>Tour:</strong> {{ $booking->tour ? $booking->tour->tour_name : 'Tour tùy chỉnh' }}</p>
                <p><strong>Số lượng người:</strong> {{ $booking->quantity }}</p>
                <p><strong>Ngày bắt đầu:</strong> {{ date('d/m/Y', strtotime($booking->start_date)) }}</p>
                <p><strong>Tổng tiền:</strong> {{ number_format($booking->total_price) }} VNĐ</p>
                <p><strong>Trạng thái:</strong>
                    @if($booking->status == 'pending')
                        Chờ xác nhận
                    @elseif($booking->status == 'confirmed')
                        Đã xác nhận
                    @elseif($booking->status == 'completed')
                        Hoàn thành
                    @else
                        {{ $booking->status }}
                    @endif
                </p>
            </div>

            <p>Đội ngũ VTravel sẽ liên hệ với bạn trong thời gian sớm nhất để xác nhận chi tiết.</p>

            <p>Nếu bạn có bất kỳ câu hỏi nào, vui lòng liên hệ với chúng tôi qua email support@vtravel.com hoặc số điện
                thoại 1900 xxxx.</p>

            <a href="{{ config('app.frontend_url') }}/account/bookings" class="btn">Xem Chi Tiết Đơn Hàng</a>
        </div>

        <div class="footer">
            <p>© {{ date('Y') }} VTravel. Tất cả các quyền được bảo lưu.</p>
            <p>Địa chỉ: 123 ABC, Quận 1, TP. Hồ Chí Minh</p>
        </div>
    </div>
</body>

</html>