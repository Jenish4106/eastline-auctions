<!DOCTYPE html>
<html>
<head>
    <title>You Have Been Outbid</title>
</head>
<body>
    <p>Hello {{ $user->first_name }} {{ $user->last_name }},</p>
    <p>You have been outbid on the following equipment:</p>
    <p><strong>Equipment:</strong> {{ $machineryName }}</p>
    <p><strong>Current Highest Bid:</strong> ${{ number_format($currentBid, 2) }}</p>
    <p>You can place a new bid if you would like to continue bidding on this equipment.</p>
    <p>Best regards,<br>{{ \App\Models\Settings::get('company_name', 'Stiopa Equipment') }} Team</p>
</body>
</html>
