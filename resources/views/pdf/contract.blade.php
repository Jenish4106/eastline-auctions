<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Equipment Sales Contract</title>
    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 13px;
            color: #000;
            line-height: 1.6;
            margin: 30px;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 25px;
        }

        .header h1 {
            margin: 0;
            font-size: 22px;
            letter-spacing: 1px;
        }

        .company-info {
            font-size: 12px;
            margin-top: 8px;
        }

        .section {
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 15px;
            font-weight: bold;
            margin-bottom: 8px;
            text-transform: uppercase;
            border-bottom: 1px solid #ccc;
            padding-bottom: 4px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        table th, table td {
            border: 1px solid #333;
            padding: 8px;
            vertical-align: top;
        }

        table th {
            background-color: #f2f2f2;
            font-weight: bold;
            width: 35%;
        }

        .terms ol {
            padding-left: 18px;
        }

        .terms li {
            margin-bottom: 6px;
        }

        .signature-table {
            width: 100%;
            margin-top: 40px;
            border: none;
        }

        .signature-table td {
            border: none;
            padding-top: 40px;
            width: 50%;
        }

        .footer {
            text-align: center;
            font-size: 11px;
            margin-top: 40px;
            color: #555;
        }
    </style>
</head>
<body>

    <!-- HEADER -->
    <div class="header">
        <h1>SALES CONTRACT</h1>
        <div class="company-info">
            <strong>RB Equipment Sales</strong><br>
            123 Industrial Road, Montgomery Village, USA<br>
            Sales: +34 520-900-1307 | Email: rb@equipmentsales.com
        </div>
        <p><strong>Contract Date:</strong> {{ $contractDate }}</p>
    </div>

    <!-- PARTIES -->
    <div class="section">
        <div class="section-title">Parties to the Contract</div>
        <p>
            This Sales Contract ("Agreement") is entered into on 
            <strong>{{ $contractDate }}</strong> between:
        </p>

        <p>
            <strong>Seller:</strong><br>
            RB Equipment Sales<br>
            123 Industrial Road, Montgomery Village, USA
        </p>

        <p>
            <strong>Buyer:</strong><br>
            {{ $user->first_name }} {{ $user->last_name }}<br>
            Email: {{ $user->email }}<br>
            Phone: {{ $user->phone_no ?? 'N/A' }}
        </p>
    </div>

    <!-- EQUIPMENT DETAILS -->
    <div class="section">
        <div class="section-title">Equipment Details</div>

        <table>
            <tr>
                <th>Equipment Name</th>
                <td>{{ trim($machinery->year . ' ' . $machinery->make . ' ' . $machinery->model) }}</td>
            </tr>
            <tr>
                <th>Year</th>
                <td>{{ $machinery->year }}</td>
            </tr>
            <tr>
                <th>Make</th>
                <td>{{ $machinery->make }}</td>
            </tr>
            <tr>
                <th>Model</th>
                <td>{{ $machinery->model }}</td>
            </tr>
            <tr>
                <th>Serial Number</th>
                <td>{{ $machinery->serial_number }}</td>
            </tr>
            <tr>
                <th>Working Hours</th>
                <td>{{ $machinery->working_hours }}</td>
            </tr>
            <tr>
                <th>Final Sale Price</th>
                <td><strong>${{ number_format($highestBid->amount, 2 ?? '') ?? '0.00' }}</strong></td>
            </tr>
            <tr>
                <th>Sale Date</th>
                <td>{{ $contractDate }}</td>
            </tr>
        </table>
    </div>

    <!-- TERMS -->
    <div class="section terms">
        <div class="section-title">Terms and Conditions</div>
        <ol>
            <li>Buyer shall complete full payment within seven (7) days from the contract date.</li>
            <li>The equipment is sold strictly on an <strong>“AS-IS, WHERE-IS”</strong> basis without any warranties.</li>
            <li>Buyer is solely responsible for transportation, loading, and insurance.</li>
            <li>Equipment must be collected within thirty (30) days from the contract date.</li>
            <li>All sales are final. No cancellations or refunds shall be permitted.</li>
            <li>This Agreement shall be governed by and construed under applicable local laws.</li>
        </ol>
    </div>

    <!-- SIGNATURES -->
    <div class="section">
        <div class="section-title">Signatures</div>

        <table class="signature-table">
            <tr>
                <td>
                    Buyer Signature:<br>
                    @if(isset($absoluteSignaturePath) && file_exists($absoluteSignaturePath))
                        <img src="{{ $absoluteSignaturePath }}" alt="Buyer Signature" style="max-width: 200px; max-height: 60px;"><br>
                    @else
                        _______________________________<br>
                    @endif
                    Name: {{ $user->first_name }} {{ $user->last_name }}<br>
                    Date: {{ $contractDate }}
                </td>
                <td>
                    Seller Signature:<br>
                    _______________________________<br>
                    Authorized Representative<br>
                    RB Equipment Sales<br>
                    Date: {{ $contractDate }}
                </td>
            </tr>
        </table>
    </div>

    <!-- FOOTER -->
    <div class="footer">
        This document constitutes a legally binding agreement upon signature by both parties.<br>
        © RB Equipment Sales – Professional Equipment Sales Since 2025
    </div>

</body>
</html>
