<!DOCTYPE html>
<html>
<head>
    <title>Order Status Update</title>
</head>
<body>
    <p>Hello {{ $user->first_name }} {{ $user->last_name }},</p>
    <p>Your order status has been updated:</p>
    <p><strong>Order ID:</strong> {{ $order->order_id }}</p>
    <p><strong>Equipment:</strong> {{ $machineryName }}</p>
    <p><strong>Status:</strong> {{ $status }}</p>
    <p>If you have any questions, please feel free to contact us.</p>
    <p>Best regards,<br>{{ \App\Models\Settings::get('company_name', 'Stiopa Equipment') }} Team</p>
</body>
</html>
