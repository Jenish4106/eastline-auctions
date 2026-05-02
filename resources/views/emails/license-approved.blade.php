<!DOCTYPE html>
<html>
<head>
    <title>License Approved</title>
</head>
<body>
    <p>Hello {{ $user->first_name }} {{ $user->last_name }},</p>
    <p>Your license has been approved!</p>
    <p>You can now participate in bidding and use the Buy It Now option on our available equipment. We look forward to doing business with you.</p>
    <p>If you have any questions, please feel free to contact us.</p>
    <p>Best regards,<br>{{ \App\Models\Settings::get('company_name', 'Mcfarland Equipment') }} Team</p>
</body>
</html>
