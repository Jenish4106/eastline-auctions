<!DOCTYPE html>
<html>
<head>
    <title>Auction Cancelled</title>
</head>
<body>
    <p>Hello {{ trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: '' }},</p>
    <p>The auction for the following equipment has been cancelled because it has been purchased through Buy Now.</p>
    <p><strong>Equipment:</strong> {{ $machineryName }}</p>
    @if(!empty($purchaserName))
        <p><strong>Purchased By:</strong> {{ $purchaserName }}</p>
    @endif
    <p>You can continue exploring other available equipment on our platform.</p>
    <p>Best regards,<br>{{ \App\Models\Settings::get('company_name', 'Eastline Equipment Sales & Auctions') }} Team</p>
</body>
</html>
