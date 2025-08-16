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
            background: linear-gradient(to right, #27ae60, #2ecc71);
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

        .status-update {
            background-color: #eafaf1;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            border-left: 4px solid #27ae60;
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

        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
            color: white;
        }

        .status-pending {
            background-color: #f39c12;
        }

        .status-confirmed {
            background-color: #3498db;
        }

        .status-completed {
            background-color: #27ae60;
        }

        .status-cancelled {
            background-color: #e74c3c;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h2>Cập Nhật Trạng Thái Đơn Đặt Tour</h2>
        </div>

        <div class="content">
            <p>Xin chào <strong>{{ $booking->user->name }}</strong>,</p>

            <p>Đơn đặt tour của bạn đã được cập nhật trạng thái.</p>

            <div class="status-update">
                <h3>Thay đổi trạng thái:</h3>
                <p>
                    <strong>Từ:</strong>
                    <span class="status-badge status-{{ $oldStatus }}">
                        @if($oldStatus == 'pending') Chờ xác nhận
                        @elseif($oldStatus == 'confirmed') Đã xác nhận
                        @elseif($oldStatus == 'completed') Hoàn thành
                        @elseif($oldStatus == 'cancelled') Đã hủy
                        @else {{ $oldStatus }}
                        @endif
                    </span>
                </p>
                <p>
                    <strong>Đến:</strong>
                    <span class="status-badge status-{{ $newStatus }}">
                        @if($newStatus == 'pending') Chờ xác nhận
                        @elseif($newStatus == 'confirmed') Đã xác nhận
                        @elseif($newStatus == 'completed') Hoàn thành
                        @elseif($newStatus == 'cancelled') Đã hủy
                        @else {{ $newStatus }}
                        @endif
                    </span>
                </p>
            </div>

            <div class="booking-details">
                <h3>Thông tin đặt tour:</h3>
                <p><strong>Mã đơn hàng:</strong> #{{ $booking->booking_id }}</p>
                <p><strong>Tour:</strong> {{ $booking->tour ? $booking->tour->tour_name : 'Tour tùy chỉnh' }}</p>
                <p><strong>Số lượng người:</strong> {{ $booking->quantity }}</p>
                <p><strong>Ngày bắt đầu:</strong> {{ date('d/m/Y', strtotime($booking->start_date)) }}</p>
                <p><strong>Tổng tiền:</strong> {{ number_format($booking->total_price) }} VNĐ</p>
            </div>

            @if($newStatus == 'confirmed')
                <p>Đơn đặt tour của bạn đã được xác nhận! Chúng tôi rất mong đợi được phục vụ bạn.</p>
                <p>Đội ngũ VTravel sẽ liên hệ với bạn sớm để cung cấp thêm thông tin chi tiết.</p>
            @elseif($newStatus == 'completed')
                <p>Tour của bạn đã hoàn thành! Cảm ơn bạn đã lựa chọn VTravel.</p>
                <p>Chúng tôi rất mong nhận được đánh giá từ bạn về trải nghiệm chuyến đi.</p>
            @endif

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