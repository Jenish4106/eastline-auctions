<!DOCTYPE html>
<html>
<head>
    <title>Contract Rejected</title>
</head>
<body>
    <p>Hello {{ $user->first_name }} {{ $user->last_name }},</p>
    <p>We regret to inform you that your contract for the machinery <strong>{{ $machineryName }}</strong> has been rejected.</p>
    <p>If you have any specific concerns or questions regarding this decision, please feel free to contact us.</p>
    <p>Best regards,<br>{{ \App\Models\Settings::get('company_name', 'Mcfarland Equipment') }} Team</p>
</body>
</html>
