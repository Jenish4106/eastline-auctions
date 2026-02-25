<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 14px;
            color: #555;
            line-height: 1.4;
        }
        .container {
            width: 100%;
            margin: 0 auto;
            padding: 20px;
        }
        .header-table {
            width: 100%;
            margin-bottom: 15px;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        .header-table td {
            vertical-align: top;
        }
        .logo {
            max-width: 135px;
            max-height: 55px;
        }
        .company-info {
            text-align: right;
            font-size: 13px;
        }
        .invoice-title {
            font-size: 20px;
            font-weight: 700;
            color: #2c3e50;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 2px;
        }
        .invoice-number {
            color: #7f8c8d;
            font-size: 13px;
            margin-bottom: 4px;
        }
        .invoice-meta {
            text-align: right;
            margin-top: 0;
            font-size: 12px;
        }
        .info-table {
            width: 100%;
            margin-bottom: 20px;
        }
        .info-table td {
            width: 50%;
            vertical-align: top;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #2c3e50;
            text-transform: uppercase;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
            margin-bottom: 10px;
            width: 90%;
        }
        .address-box {
            font-size: 13px;
            color: #444;
        }
        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            margin-top: 10px;
        }
        table.items th {
            background-color: #f8f9fa;
            color: #2c3e50;
            font-weight: bold;
            text-align: left;
            padding: 12px;
            border-bottom: 2px solid #ddd;
        }
        table.items td {
            padding: 12px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }
        table.items td:last-child {
            font-weight: 600;
            color: #2c3e50;
        }
        .machinery-image {
            width: 80px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #eee;
        }
        .total-section {
            width: 100%;
            text-align: right;
        }
        .total-table {
            width: 40%;
            float: right;
            border-collapse: collapse;
        }
        .total-table td {
            padding: 8px;
            border-bottom: 1px solid #eee;
        }
        .total-row {
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
            border-top: 2px solid #2c3e50;
        }
        .bank-box {
            background: #f9fafb;
            border: 1px solid #ddd;
            padding: 12px;
            font-size: 12px;
            line-height: 1.6;
            width: 55%;
            float: left;
            text-align: left;
            box-sizing: border-box;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 12px;
            color: #999;
            border-top: 1px solid #eee;
            padding-top: 20px;
            clear: both;
        }
    </style>
</head>
<body>
    <div class="container">
        <table class="header-table">
            <tr>
                <td style="width: 50%;">
                    @if(isset($companyInfo['logo']) && !empty($companyInfo['logo']))
                         <img src="{{ $companyInfo['logo'] }}" class="logo">
                    @elseif(isset($companyInfo['logoUrl']) && !empty($companyInfo['logoUrl']))
                         <img src="{{ $companyInfo['logoUrl'] }}" class="logo">
                    @else
                         <h2 style="margin:0; color:#2c3e50;">{{ $companyInfo['name'] }}</h2>
                    @endif
                </td>

                <td style="width: 50%; text-align: right;">
                    <div class="invoice-title">INVOICE</div>
                    <div class="invoice-number">#{{ $order->order_id }}</div>
                    <div class="invoice-meta">
                        <strong>Date:</strong> {{ $order->purchase_date->format('F d, Y') }}<br>
                    </div>
                </td>
            </tr>
        </table>
        
        <div class="bank-box">
            <div style="margin-bottom: 5px;">
                <strong style="font-size: 13px; color: #2c3e50;">FROM:</strong><br>
                <strong>{{ $companyInfo['name'] }}</strong><br>
                {{ $companyInfo['address'] }}
            </div>
            @if(isset($companyInfo['bankDetails']) && !empty($companyInfo['bankDetails']))
            <strong style="font-size: 13px; color: #2c3e50;">Bank Details:</strong><br>
            {!! $companyInfo['bankDetails'] !!}
            @endif
        </div>

        <table class="info-table">
            <tr>
                <td>
                    <div class="section-title">Bill To</div>
                    <div class="address-box">
                        <strong>{{ $order->user->first_name }} {{ $order->user->last_name }}</strong><br>
                        @if($order->billing_company) {{ $order->billing_company }}<br> @endif
                        {{ $order->billing_street }}<br>
                        {{ $order->billing_city }}, {{ $order->billing_state }} {{ $order->billing_zip }}<br>
                        {{ $order->billing_country }}<br>
                        <br>
                        <strong>Phone:</strong> {{ $order->user->phone_no }}
                    </div>
                </td>
                <td>
                    <div class="section-title">Ship To</div>
                    <div class="address-box">
                        @if(!$order->shipping_same_as_billing)
                            <strong>{{ $order->user->first_name }} {{ $order->user->last_name }}</strong><br>
                            {{ $order->shipping_street }}<br>
                            {{ $order->shipping_city }}, {{ $order->shipping_state }} {{ $order->shipping_zip }}<br>
                            {{ $order->shipping_country }}
                        @else
                            <em style="color: #7f8c8d;">(Same as Billing Address)</em>
                        @endif
                    </div>
                </td>
            </tr>
        </table>

        <table class="items">
            <thead>
                <tr>
                    <th style="width: 15%;">Image</th>
                    <th style="width: 45%;">Description</th>
                    <th style="width: 20%;">Year</th>
                    <th style="width: 20%; text-align: right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        @if(isset($machineryImage) && $machineryImage)
                            <img src="{{ $machineryImage }}" class="machinery-image">
                        @elseif(isset($machineryImageUrl) && $machineryImageUrl)
                            <img src="{{ $machineryImageUrl }}" class="machinery-image">
                        @else
                            <div style="width:80px; height:60px; background:#eee; display:flex; align-items:center; justify-content:center; color:#999; font-size:10px;">No Image</div>
                        @endif
                    </td>
                    <td>
                        <strong>{{ $order->machinery->make }} {{ $order->machinery->model }}</strong><br>
                        <span style="color: #7f8c8d; font-size: 12px;">Serial: {{ $order->machinery->serial_number ?? 'N/A' }}</span>
                    </td>
                    <td>{{ $order->machinery->year }}</td>
                    <td style="text-align: right; font-weight: bold;">{{ number_format($order->price, 2) }}</td>
                </tr>
            </tbody>
        </table>

        <div class="total-section">
            <table class="total-table">
                <tr>
                    <td>Subtotal:</td>
                    <td style="text-align: right;">{{ number_format($order->price, 2) }}</td>
                </tr>
                <tr>
                    <td>Shipping Cost:</td>
                    <td style="text-align: right;">{{ number_format($order->shipping_cost, 2) }}</td>
                </tr>
                <tr>
                    <td>Tax (0%):</td>
                    <td style="text-align: right;">0.00</td>
                </tr>
                <tr class="total-row">
                    <td>Total:</td>
                    <td style="text-align: right;">{{ number_format($order->price + $order->shipping_cost, 2) }}</td>
                </tr>
            </table>
            <div style="clear: both;"></div>
        </div>

        <div class="footer">
            <p>Thank you for your business!</p>
            <p>{{ $companyInfo['name'] }} | {{ $companyInfo['email'] }}</p>
        </div>
    </div>
</body>
</html>
