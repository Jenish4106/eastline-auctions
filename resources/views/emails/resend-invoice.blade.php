<!DOCTYPE html>
<html>
<head>
    <title>Invoice for Order</title>
</head>
<body>
    <p>Hello {{ $user->first_name }} {{ $user->last_name }},</p>
    <p>Please find the copy of the invoice for your purchase of <strong>{{ $machineryName }}</strong> attached to this email.</p>
    <p><strong>Order ID:</strong> {{ $order->order_id }}</p>
    <p>If you have any questions or require further assistance, please feel free to contact us.</p>
    <p>Best regards,<br>{{ \App\Models\Settings::get('company_name', 'Eastline Equipment Auctions') }} Team</p>
</body>
</html>
