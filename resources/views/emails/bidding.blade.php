<!DOCTYPE html>
<html>
<head>
    <title>Bid Placed Successfully</title>
</head>
<body>
    <p>Hello {{ $user->first_name }} {{ $user->last_name }},</p>
    <p>Your bid has been placed successfully!</p>
    <p><strong>Equipment:</strong> {{ $machineryName }}</p>
    <p><strong>Bid Amount:</strong> ${{ number_format($bidAmount, 2) }}</p>
    <p>You will be notified if you are outbid by another user.</p>
    <p>Best regards,<br>{{ \App\Models\Settings::get('company_name', 'Eastline Equipment Auctions') }} Team</p>
</body>
</html>
