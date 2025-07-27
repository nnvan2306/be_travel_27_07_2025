<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mã OTP Xác Thực</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .email-container {
            background: #ffffff;
            max-width: 600px;
            width: 100%;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            animation: slideIn 0.6s ease-out;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 30px;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }
        
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .header h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }
        
        .header p {
            font-size: 16px;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }
        
        .lock-icon {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            position: relative;
            z-index: 1;
        }
        
        .lock-icon::before {
            content: '🔐';
            font-size: 32px;
        }
        
        .content {
            padding: 50px 30px;
            text-align: center;
        }
        
        .greeting {
            font-size: 24px;
            color: #333;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .message {
            font-size: 16px;
            color: #666;
            line-height: 1.6;
            margin-bottom: 40px;
        }
        
        .otp-container {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            border-radius: 15px;
            padding: 30px;
            margin: 30px 0;
            position: relative;
            overflow: hidden;
        }
        
        .otp-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
            animation: shine 2s ease-in-out infinite;
        }
        
        @keyframes shine {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        
        .otp-label {
            color: white;
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 15px;
            position: relative;
            z-index: 1;
        }
        
        .otp-code {
            background: rgba(255, 255, 255, 0.9);
            color: #333;
            font-size: 36px;
            font-weight: 800;
            letter-spacing: 8px;
            padding: 20px 30px;
            border-radius: 12px;
            display: inline-block;
            font-family: 'Courier New', monospace;
            box-shadow: inset 0 2px 10px rgba(0, 0, 0, 0.1);
            position: relative;
            z-index: 1;
            transition: transform 0.2s ease;
        }
        
        .otp-code:hover {
            transform: scale(1.05);
        }
        
        .timer-container {
            margin-top: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .timer-icon {
            width: 20px;
            height: 20px;
            background: #ff6b6b;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
            animation: pulse 1s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        .timer-text {
            color: #ff6b6b;
            font-weight: 600;
            font-size: 14px;
        }
        
        .instructions {
            background: #f8f9ff;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin: 30px 0;
            border-radius: 0 10px 10px 0;
        }
        
        .instructions h3 {
            color: #333;
            font-size: 18px;
            margin-bottom: 10px;
        }
        
        .instructions p {
            color: #666;
            line-height: 1.6;
        }
        
        .footer {
            background: #f8f9fa;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #eee;
        }
        
        .footer p {
            color: #888;
            font-size: 14px;
            line-height: 1.6;
        }
        
        .company-name {
            color: #667eea;
            font-weight: 600;
        }
        
        .copy-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 15px;
        }
        
        .copy-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        @media (max-width: 600px) {
            .email-container {
                margin: 10px;
                border-radius: 15px;
            }
            
            .content {
                padding: 30px 20px;
            }
            
            .otp-code {
                font-size: 28px;
                letter-spacing: 4px;
                padding: 15px 20px;
            }
            
            .header {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <div class="lock-icon"></div>
            <h1>Xác Thực Tài Khoản</h1>
            <p>Mã OTP bảo mật của bạn</p>
        </div>
        
        <div class="content">
            <h2 class="greeting">Xin chào!</h2>
            <p class="message">
                Chúng tôi đã nhận được yêu cầu xác thực từ tài khoản của bạn. 
                Vui lòng sử dụng mã OTP bên dưới để hoàn tất quá trình xác thực.
            </p>
            
            <div class="otp-container">
                <div class="otp-label">MÃ OTP CỦA BẠN</div>
                <div class="otp-code" id="otpCode">{{ $code }}</div>
                <button class="copy-btn" onclick="copyOTP()">📋 Sao chép mã</button>
            </div>
            
            <div class="timer-container">
                <div class="timer-icon">⏱</div>
                <span class="timer-text">Mã có hiệu lực trong <span id="countdown">2:00</span> phút</span>
            </div>
            
            <div class="instructions">
                <h3>📋 Hướng dẫn sử dụng:</h3>
                <p>
                    • Nhập mã OTP này vào trang xác thực<br>
                    • Mã chỉ có hiệu lực trong 2 phút<br>
                    • Không chia sẻ mã này với bất kỳ ai<br>
                    • Nếu bạn không yêu cầu mã này, vui lòng bỏ qua email
                </p>
            </div>
        </div>
        
        <div class="footer">
            <p>
                Email này được gửi tự động từ hệ thống bảo mật của <span class="company-name">Vtravel</span>.<br>
                Vui lòng không trả lời email này. Nếu cần hỗ trợ, liên hệ: qtuan2502@gmail.com
            </p>
        </div>
    </div>

    <script>
        // Countdown
        let timeLeft = 120;
        const countdownElement = document.getElementById('countdown');
        
        const timer = setInterval(() => {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            countdownElement.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
            
            if (timeLeft <= 0) {
                clearInterval(timer);
                countdownElement.textContent = "Hết hạn";
                countdownElement.style.color = "#ff4757";
            }
            timeLeft--;
        }, 1000);
        
        // Copy OTP function
        function copyOTP() {
            const otpCode = document.getElementById('otpCode').textContent;
            navigator.clipboard.writeText(otpCode).then(() => {
                const btn = document.querySelector('.copy-btn');
                const originalText = btn.innerHTML;
                btn.innerHTML = '✓ Đã sao chép!';
                btn.style.background = '#2ed573';
                
                setTimeout(() => {
                    btn.innerHTML = originalText;
                    btn.style.background = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
                }, 2000);
            });
        }
        
        // Add some interactive effects
        document.addEventListener('DOMContentLoaded', function() {
            const otpCode = document.getElementById('otpCode');
            
            // Add click effect to OTP code
            otpCode.addEventListener('click', function() {
                this.style.transform = 'scale(1.1)';
                setTimeout(() => {
                    this.style.transform = 'scale(1)';
                }, 200);
            });
        });
    </script>
</body>
</html>