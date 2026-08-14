<!DOCTYPE html>
<html>
<head>
    <title>Password Reset Successful</title>
</head>
<body>
    <p>Hello {{ $user->first_name }} {{ $user->last_name }},</p>
    <p>Your password has been successfully reset.</p>
    <p>If you did not make this change, please contact us immediately.</p>
    <p>Best regards,<br>{{ \App\Models\Settings::get('company_name', 'Eastline Equipment Sales & Auctions') }} Team</p>
</body>
</html>
