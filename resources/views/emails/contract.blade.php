<!DOCTYPE html>
<html>
<head>
    <title>Equipment Sales Contract</title>
</head>
<body>
    <p>Hello,</p>
    <p>Congratulations on winning the bid for the following equipment:</p>
    <p><strong>Equipment:</strong> {{ $machineryName }}</p>
    <p><strong>Final Bid Amount:</strong> ${{ number_format($finalBidAmount, 2) }}</p>
    <p><strong>Winning Date:</strong> {{ $winningDate }}</p>
    <p>We have sent you the contract. Please log in to the dashboard, review it, sign it, and submit it.</p>
    <p>Best regards,<br>{{ \App\Models\Settings::get('company_name', 'Eastline Equipment Auctions') }} Team</p>
</body>
</html>
