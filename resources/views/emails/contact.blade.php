<!DOCTYPE html>
<html>
<head>
    <title>Contact Form Submission</title>
</head>
<body>
    <p>Hello,</p>
    <p>You have received a new contact form submission:</p>
    <p><strong>Name:</strong> {{ $fullName }}</p>
    <p><strong>Email:</strong> {{ $email }}</p>
    @if($phone)
    <p><strong>Phone:</strong> {{ $phone }}</p>
    @endif
    <p><strong>Message:</strong></p>
    <p>{{ $userMessage }}</p>
    <p>Best regards,<br>{{ \App\Models\Settings::get('company_name', 'Eastline Equipment Sales & Auctions') }} Team</p>
</body>
</html>