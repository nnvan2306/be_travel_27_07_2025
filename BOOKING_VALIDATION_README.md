# Hướng dẫn sử dụng tính năng Validation Booking

## Tổng quan

Tính năng này cho phép kiểm tra availability của các dịch vụ (xe khách, xe máy) trước khi đặt tour để tránh việc overbooking.

## Các thay đổi chính

### 1. Database Changes

#### Migration: `2025_08_18_000000_add_booking_validation_fields.php`

- Thêm trường `total_seats` vào bảng `bus_routes` để lưu tổng số ghế
- Thêm trường `total_quantity` vào bảng `motorbikes` để lưu tổng số xe máy
- Thêm trường `end_date` vào bảng `bookings` để lưu ngày kết thúc tour
- Thêm trường `service_quantity` vào bảng `bookings` để lưu số lượng dịch vụ đã đặt

### 2. Models Updates

#### BusRoute Model
- Thêm `total_seats` vào `$fillable` và `$casts`

#### Motorbike Model  
- Thêm `total_quantity` vào `$fillable` và `$casts`

#### Booking Model
- Thêm `service_quantity` vào `$fillable` và `$casts`

### 3. Service Class

#### BookingValidationService
- `checkBusRouteAvailability()`: Kiểm tra availability của xe khách
- `checkMotorbikeAvailability()`: Kiểm tra availability của xe máy
- `validateBookingServices()`: Kiểm tra tất cả dịch vụ trong một booking
- `calculateEndDate()`: Tính toán ngày kết thúc dựa trên tour duration

### 4. API Endpoints

#### Public Routes (không cần auth)
```php
POST /api/bookings/check-bus-availability
POST /api/bookings/check-motorbike-availability  
POST /api/bookings/check-availability
```

#### Request Parameters
```json
{
    "start_date": "2025-08-20",
    "end_date": "2025-08-23", 
    "quantity": 2,
    "bus_route_id": 1,
    "motorbike_id": 1
}
```

#### Response
```json
{
    "success": true,
    "data": {
        "valid": true,
        "errors": {}
    }
}
```

### 5. Frontend Updates

#### BookingModal.tsx
- Thêm state `availabilityErrors` để hiển thị lỗi
- Thêm validation trước khi đặt tour
- Hiển thị thông báo lỗi availability
- Disable nút đặt tour khi có lỗi

## Cách sử dụng

### 1. Chạy Migration
```bash
php artisan migrate
```

### 2. Chạy Seeder để cập nhật dữ liệu
```bash
php artisan db:seed --class=UpdateServiceDataSeeder
```

### 3. Cập nhật dữ liệu dịch vụ

#### Cập nhật xe khách
```sql
UPDATE bus_routes SET total_seats = 45 WHERE total_seats = 0;
```

#### Cập nhật xe máy  
```sql
UPDATE motorbikes SET total_quantity = 5 WHERE total_quantity = 0;
```

### 4. Test API

#### Kiểm tra availability xe khách
```bash
curl -X POST http://localhost:8000/api/bookings/check-bus-availability \
  -H "Content-Type: application/json" \
  -d '{
    "bus_route_id": 1,
    "start_date": "2025-08-20",
    "end_date": "2025-08-23", 
    "quantity": 2
  }'
```

#### Kiểm tra availability xe máy
```bash
curl -X POST http://localhost:8000/api/bookings/check-motorbike-availability \
  -H "Content-Type: application/json" \
  -d '{
    "motorbike_id": 1,
    "start_date": "2025-08-20",
    "end_date": "2025-08-23",
    "quantity": 1
  }'
```

## Logic hoạt động

### 1. Kiểm tra xe khách
- Lấy tổng số ghế (`total_seats`) của xe khách
- Tính số ghế đã được đặt trong khoảng thời gian
- Kiểm tra số ghế còn trống có đủ cho booking mới không

### 2. Kiểm tra xe máy
- Lấy tổng số xe máy (`total_quantity`) có sẵn
- Tính số xe máy đã được đặt trong khoảng thời gian  
- Kiểm tra số xe máy còn trống có đủ cho booking mới không

### 3. Tính toán thời gian
- Sử dụng `start_date` và `end_date` để kiểm tra overlap
- Chỉ tính các booking có status `pending` hoặc `confirmed`
- Loại trừ booking hiện tại khi update

## Lưu ý

1. **Tour Duration**: Hệ thống sẽ tự động tính `end_date` dựa trên `duration` của tour
2. **Custom Tour**: Với custom tour, `end_date` được tính dựa trên `duration` được cung cấp
3. **Validation**: Tất cả validation được thực hiện trước khi tạo booking
4. **Error Handling**: Lỗi được hiển thị rõ ràng cho user

## Troubleshooting

### Lỗi "Xe khách không tồn tại"
- Kiểm tra `bus_route_id` có tồn tại trong database không
- Kiểm tra `is_deleted` có phải là `active` không

### Lỗi "Chỉ còn X ghế trống"
- Kiểm tra `total_seats` của xe khách
- Kiểm tra các booking khác trong cùng thời gian

### Lỗi "Xe máy không tồn tại"  
- Kiểm tra `motorbike_id` có tồn tại trong database không
- Kiểm tra `is_deleted` có phải là `active` không
