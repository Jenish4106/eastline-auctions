<!DOCTYPE html>
<html>
<head>
    <title>Equipment Sales Contract</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .company-info { font-size: 14px; line-height: 1.5; }
        .contract-details { margin: 20px 0; }
        .contract-details table { width: 100%; border-collapse: collapse; }
        .contract-details th, .contract-details td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .contract-details th { background-color: #f2f2f2; }
        .signature-section { margin-top: 40px; }
        .footer { margin-top: 40px; text-align: center; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="contract-details">
        <p>Dear Winner,</p>
        <p>Congratulations on winning the bid for the following equipment:</p>
        
        <table>
            <tr>
                <th>Machinery Name</th>
                <th>Final Bid Amount</th>
                <th>Winning Date</th>
            </tr>
            <tr>
                <td>{{ $machineryName }}</td>
                <td>{{ $finalBidAmount }}</td>
                <td>{{ $winningDate }}</td>
            </tr>
        </table>
        
        <p>We have sent you the contract. Please log in to the dashboard, review it, sign it, and submit it.</p>
    </div>
    
    <div class="signature-section">
        <p>Best regards,</p>
        <p><strong>RB Equipment Sales Team</strong></p>
    </div>
    
    <div class="footer">
        <p>This is an automated message from RB Equipment Sales. Please do not reply to this email.</p>
    </div>
</body>
</html>
