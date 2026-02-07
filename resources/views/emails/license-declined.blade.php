<!DOCTYPE html>
<html>
<head>
    <title>License Declined</title>
</head>
<body>
    <p>Hello {{ $user->first_name }} {{ $user->last_name }},</p>
    <p>Unfortunately, your license application has been declined.</p>
    <p>If you have any questions or would like to submit a new license application, please contact us.</p>
    <p>Best regards,<br>{{ \App\Models\Settings::get('company_name', 'Stiopa Equipment') }} Team</p>
</body>
</html>
