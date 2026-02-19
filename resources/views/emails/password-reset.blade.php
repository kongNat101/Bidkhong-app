<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; background-color: #f5f5f5; margin: 0; padding: 20px; }
        .container { max-width: 500px; margin: 0 auto; background: white; border-radius: 12px; padding: 40px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .logo { text-align: center; font-size: 28px; font-weight: bold; color: #4F46E5; margin-bottom: 30px; }
        .title { text-align: center; font-size: 20px; color: #333; margin-bottom: 10px; }
        .description { text-align: center; color: #666; font-size: 14px; margin-bottom: 30px; }
        .code-box { text-align: center; background: #F3F4F6; border-radius: 8px; padding: 20px; margin-bottom: 30px; }
        .code { font-size: 36px; font-weight: bold; letter-spacing: 8px; color: #4F46E5; }
        .warning { background: #FEF3C7; border-radius: 8px; padding: 12px 16px; font-size: 13px; color: #92400E; margin-bottom: 20px; }
        .expire { text-align: center; color: #999; font-size: 13px; }
        .footer { text-align: center; color: #999; font-size: 12px; margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">BidKhong</div>
        <div class="title">Password Reset</div>
        <div class="description">Use the code below to reset your password.</div>

        <div class="code-box">
            <div class="code">{{ $token }}</div>
        </div>

        <div class="warning">
            Do not share this code with anyone. BidKhong staff will never ask for this code.
        </div>

        <div class="expire">This code will expire in 60 minutes.</div>

        <div class="footer">
            If you did not request a password reset, you can ignore this email.<br>
            &copy; {{ date('Y') }} BidKhong. All rights reserved.
        </div>
    </div>
</body>
</html>
