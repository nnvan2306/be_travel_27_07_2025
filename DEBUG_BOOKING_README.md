# Hướng dẫn Debug Booking Validation

## Vấn đề hiện tại
Xe khách 17 chỗ nhưng khi chọn 14 người lại báo lỗi "Chỉ còn 0 ghế trống"

## Nguyên nhân có thể
1. **Dữ liệu `total_seats` không đúng**: Có thể `total_seats = 0` hoặc `null`
2. **Logic tính toán sai**: Có thể đang tính sai số ghế đã đặt
3. **Dữ liệu booking cũ**: Có thể có booking cũ đang chiếm hết ghế

## Cách Debug

### 1. Sử dụng API Debug
```bash
# Kiểm tra dữ liệu xe khách và booking
curl -X POST http://localhost:8000/api/bookings/debug-bus-route \
  -H "Content-Type: application/json" \
  -d '{
    "bus_route_id": 1,
    "start_date": "2025-08-20",
    "end_date": "2025-08-23"
  }'
```

### 2. Cập nhật dữ liệu xe khách
```bash
# Cập nhật total_seats cho tất cả xe khách
curl -X POST http://localhost:8000/api/bookings/update-bus-route-data
```

### 3. Kiểm tra Log
Xem log Laravel để debug:
```bash
tail -f storage/logs/laravel.log
```

### 4. Kiểm tra Database trực tiếp
```sql
-- Kiểm tra dữ liệu xe khách
SELECT bus_route_id, route_name, seats, total_seats, is_deleted 
FROM bus_routes 
WHERE bus_route_id = 1;

-- Kiểm tra booking trong khoảng thời gian
SELECT booking_id, quantity, service_quantity, start_date, end_date, status, is_deleted
FROM bookings 
WHERE bus_route_id = 1 
  AND is_deleted = 'active'
  AND status IN ('pending', 'confirmed')
  AND (
    (start_date >= '2025-08-20' AND start_date <= '2025-08-23') OR
    (end_date >= '2025-08-20' AND end_date <= '2025-08-23') OR
    (start_date <= '2025-08-20' AND end_date >= '2025-08-23')
  );
```

## Các bước khắc phục

### Bước 1: Cập nhật dữ liệu xe khách
```sql
UPDATE bus_routes 
SET total_seats = CASE 
    WHEN total_seats > 0 THEN total_seats 
    WHEN seats > 0 THEN seats 
    ELSE 45 
END
WHERE total_seats = 0 OR total_seats IS NULL;
```

### Bước 2: Kiểm tra booking cũ
```sql
-- Xóa hoặc cập nhật booking cũ không hợp lệ
UPDATE bookings 
SET is_deleted = 'deleted' 
WHERE status = 'cancelled' 
  AND is_deleted = 'active';
```

### Bước 3: Cập nhật end_date cho booking
```sql
-- Cập nhật end_date cho booking chưa có
UPDATE bookings 
SET end_date = start_date 
WHERE end_date IS NULL;
```

## Test Case

### Test 1: Xe khách 17 chỗ, đặt 14 người
1. Chọn xe khách có 17 chỗ
2. Chọn ngày khởi hành
3. Đặt 14 người
4. Kiểm tra không có lỗi availability

### Test 2: Xe khách đã có booking
1. Tạo booking cho xe khách với 10 người
2. Thử đặt thêm 8 người (tổng 18 > 17)
3. Kiểm tra có lỗi availability

### Test 3: Xe khách không tồn tại
1. Thử đặt xe khách với ID không tồn tại
2. Kiểm tra có lỗi "Xe khách không tồn tại"

## Log Debug
Khi có lỗi, kiểm tra log để xem:
- `total_seats` của xe khách
- `booked_seats` đã tính
- `available_seats` còn lại
- `requested_quantity` yêu cầu

## Lưu ý
- Đảm bảo `total_seats` > 0 cho tất cả xe khách
- Kiểm tra booking có `status` đúng (`pending`, `confirmed`)
- Kiểm tra `is_deleted = 'active'` cho booking hợp lệ
- Logic overlap thời gian phải chính xác
