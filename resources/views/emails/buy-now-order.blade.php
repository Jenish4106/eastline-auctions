<!DOCTYPE html>
<html>
<head>
    <title>Order Confirmation</title>
</head>
<body>
    <p>Hello {{ $user->first_name }} {{ $user->last_name }},</p>
    <p>Thank you for your purchase!</p>
    <p><strong>Order ID:</strong> {{ $order->order_id }}</p>
    <p><strong>Equipment:</strong> {{ $machineryName }}</p>
    <p><strong>Price:</strong> ${{ number_format($order->price, 2) }}</p>
    <p><strong>Purchase Date:</strong> {{ $order->purchase_date->format('Y-m-d') }}</p>
    <p>Your order is being processed. You will receive updates on the status of your order.</p>
    <p>Best regards,<br>{{ \App\Models\Settings::get('company_name', 'Stiopa Equipment') }} Team</p>
</body>
</html>
