<!DOCTYPE html>
<html>
<head>
    <title>Welcome to {{ \App\Models\Settings::get('company_name', 'Eastline Equipment Auctions') }}</title>
</head>
<body>
    <p>Hello {{ $user->first_name }} {{ $user->last_name }},</p>
    <p>Thank you for registering with {{ \App\Models\Settings::get('company_name', 'Eastline Equipment Auctions') }}!</p>
    <p>Your account has been successfully created. You can now log in and start browsing our equipment inventory.</p>
    <p>If you have any questions, please feel free to contact us.</p>
    <p>Best regards,<br>{{ \App\Models\Settings::get('company_name', 'Eastline Equipment Auctions') }} Team</p>
</body>
</html>
