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
            background: linear-gradient(to right, #e74c3c, #c0392b);
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

        .cancel-reason {
            background-color: #ffeeee;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            border-left: 4px solid #e74c3c;
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
            <h2>Thông Báo Hủy Đơn Đặt Tour</h2>
        </div>

        <div class="content">
            <p>Xin chào <strong>{{ $booking->user->name }}</strong>,</p>

            <p>Đơn đặt tour của bạn đã được hủy.</p>

            <div class="booking-details">
                <h3>Thông tin đặt tour:</h3>
                <p><strong>Mã đơn hàng:</strong> #{{ $booking->booking_id }}</p>
                <p><strong>Tour:</strong> {{ $booking->tour ? $booking->tour->tour_name : 'Tour tùy chỉnh' }}</p>
                <p><strong>Số lượng người:</strong> {{ $booking->quantity }}</p>
                <p><strong>Ngày bắt đầu:</strong> {{ date('d/m/Y', strtotime($booking->start_date)) }}</p>
                <p><strong>Tổng tiền:</strong> {{ number_format($booking->total_price) }} VNĐ</p>
                <p><strong>Trạng thái:</strong> <span style="color: #e74c3c;">Đã hủy</span></p>
            </div>

            @if($reason)
                <div class="cancel-reason">
                    <h3>Lý do hủy:</h3>
                    <p>{{ $reason }}</p>
                </div>
            @endif

            <p>Nếu bạn có bất kỳ câu hỏi nào, vui lòng liên hệ với chúng tôi qua email support@vtravel.com hoặc số điện
                thoại 1900 xxxx.</p>

            <p>Chúng tôi rất tiếc vì sự bất tiện này và hy vọng sẽ được phục vụ bạn trong tương lai.</p>

            <a href="{{ config('app.frontend_url') }}/tours" class="btn">Khám Phá Các Tour Khác</a>
        </div>

        <div class="footer">
            <p>© {{ date('Y') }} VTravel. Tất cả các quyền được bảo lưu.</p>
            <p>Địa chỉ: 123 ABC, Quận 1, TP. Hồ Chí Minh</p>
        </div>
    </div>
</body>

</html>