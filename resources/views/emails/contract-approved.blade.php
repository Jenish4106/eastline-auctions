<!DOCTYPE html>
<html>
<head>
    <title>Contract Approved</title>
</head>
<body>
    <p>Hello {{ $user->first_name }} {{ $user->last_name }},</p>
    <p>We are pleased to inform you that your contract for the machinery <strong>{{ $machineryName }}</strong> has been approved.</p>
    <p>Please find the sales contract attached to this email.</p>
    <p>If you have any questions, please feel free to contact us.</p>
    <p>Best regards,<br>{{ \App\Models\Settings::get('company_name', 'Eastline Equipment Auctions') }} Team</p>
</body>
</html>
