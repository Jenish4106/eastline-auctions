<!DOCTYPE html>
<html>
<head>
    <title>Contact Form Submission</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
            border-bottom: 3px solid #007bff;
        }
        .content {
            padding: 30px;
            background-color: #ffffff;
            border: 1px solid #e9ecef;
        }
        .info-box {
            background-color: #f8f9fa;
            padding: 15px;
            margin: 15px 0;
            border-left: 4px solid #007bff;
            border-radius: 4px;
        }
        .info-label {
            font-weight: bold;
            color: #495057;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 0.9em;
            color: #6c757d;
            padding: 20px;
        }
        .highlight {
            background-color: #e7f3ff;
            padding: 3px 6px;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>New Contact Form Submission</h1>
        <p>You have received a new message from your website contact form</p>
    </div>
    
    <div class="content">
        <h2>Contact Information</h2>
        
        <div class="info-box">
            <p><span class="info-label">Name:</span> <span class="highlight">{{ $fullName }}</span></p>
        </div>
        
        <div class="info-box">
            <p><span class="info-label">Email:</span> <span class="highlight">{{ $email }}</span></p>
        </div>
        
        @if($phone)
        <div class="info-box">
            <p><span class="info-label">Phone:</span> <span class="highlight">{{ $phone }}</span></p>
        </div>
        @endif
        
        <div class="info-box">
            <h3><span class="info-label">Message:</span></h3>
            <p style="margin-top: 10px; white-space: pre-wrap;">{{ $userMessage }}</p>
        </div>
        
        <hr style="margin: 25px 0; border: none; border-top: 1px solid #e9ecef;">
        
        <p><strong>This message was sent on:</strong> {{ now()->format('F j, Y \a\t g:i A') }}</p>
    </div>
    
    <div class="footer">
        <p>This is an automated message from {{ config('app.name', 'RB Equipment Sales') }} website.</p>
        <p>Please do not reply directly to this email.</p>
    </div>
</body>
</html>