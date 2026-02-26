<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice</title>

    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 12px;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 100%;
            padding: 15px;
            padding-bottom: 60px;
            min-height: 100vh;
            position: relative;
        }

        .header-table {
            width: 100%;
            border-bottom: 2px solid #ddd;
            margin-bottom: 15px;
        }

        .logo {
            max-width: 120px;
            max-height: 50px;
        }

        .company-info {
            font-size: 12px;
            line-height: 1.4;
            margin-top: 5px;
        }

        .invoice-title {
            font-size: 22px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .invoice-meta {
            font-size: 12px;
            margin-top: 5px;
        }

        .info-table {
            width: 100%;
            margin-top: 10px;
            margin-bottom: 15px;
        }

        .section-title {
            font-weight: bold;
            border-bottom: 1px solid #ccc;
            margin-bottom: 5px;
            padding-bottom: 3px;
        }

        .address-box {
            font-size: 12px;
            line-height: 1.5;
        }

        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        table.items th {
            background: #f2f2f2;
            padding: 8px;
            border: 1px solid #ddd;
            font-size: 12px;
        }

        table.items td {
            padding: 8px;
            border: 1px solid #ddd;
            font-size: 12px;
        }

        .machinery-image {
            width: 70px;
            height: 50px;
        }

        .summary-table {
            width: 100%;
            margin-top: 25px;
        }

        .bank-box {
            width: 50%;
            border: 1px solid #ccc;
            padding: 14px;
            font-size: 12px;
            vertical-align: top;
        }

        .total-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .total-table td {
            padding: 6px;
            border-bottom: 1px solid #eee;
        }

        .total-row {
            font-size: 15px;
            font-weight: bold;
            border-top: 2px solid #000;
        }

        .footer {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            font-size: 11px;
            text-align: center;
            border-top: 1px solid #eee;
            padding: 12px 15px;
        }
    </style>
</head>
<body>

<div class="container">

    <table class="header-table">
        <tr>
            <td width="50%" valign="top">
                
                @if(isset($companyInfo['logo']) && $companyInfo['logo'])
                    <img src="{{ $companyInfo['logo'] }}" class="logo"><br>
                @elseif(isset($companyInfo['logoUrl']) && $companyInfo['logoUrl'])
                    <img src="{{ $companyInfo['logoUrl'] }}" class="logo"><br>
                @endif

            </td>

            <td width="50%" align="right" valign="top">
                <div class="invoice-title">INVOICE</div>
                <div class="invoice-meta">
                    <strong>#{{ $order->order_id }}</strong><br>
                    Date: {{ $order->purchase_date->format('F d, Y') }}
                </div>
                <div class="company-info" style="text-align: right;">
                    <strong>{{ $companyInfo['beneficiary_name'] }}</strong><br>
                    {{ $companyInfo['beneficiary_address'] }}
                </div>
            </td>
        </tr>
    </table>

    <table class="info-table">
        <tr>
            <td width="50%" valign="top">
                <div class="section-title">Bill To</div>
                <div class="address-box">
                    <strong>{{ $order->user->first_name }} {{ $order->user->last_name }}</strong><br>
                    @if($order->billing_company)
                        {{ $order->billing_company }}<br>
                    @endif
                    {{ $order->billing_street }}<br>
                    {{ $order->billing_city }}, {{ $order->billing_state }} {{ $order->billing_zip }}<br>
                    {{ $order->billing_country }}<br><br>
                    Phone: {{ $order->user->phone_no }}
                </div>
            </td>

            <td width="50%" valign="top">
                <div class="section-title">Ship To</div>
                <div class="address-box">
                    @if(!$order->shipping_same_as_billing)
                        <strong>{{ $order->user->first_name }} {{ $order->user->last_name }}</strong><br>
                        {{ $order->shipping_street }}<br>
                        {{ $order->shipping_city }}, {{ $order->shipping_state }} {{ $order->shipping_zip }}<br>
                        {{ $order->shipping_country }}
                    @else
                        Same as Billing Address
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
        <tr>
            <th width="15%">Image</th>
            <th width="45%">Description</th>
            <th width="15%">Year</th>
            <th width="25%" align="right">Amount</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>
                @if(isset($machineryImage) && $machineryImage)
                    <img src="{{ $machineryImage }}" class="machinery-image">
                @else
                    No Image
                @endif
            </td>

            <td>
                <strong>{{ $order->machinery->make }} {{ $order->machinery->model }}</strong><br>
                Serial: {{ $order->machinery->serial_number ?? 'N/A' }}
            </td>

            <td>{{ $order->machinery->year }}</td>

            <td align="right">
                {{ number_format($order->price, 2) }}
            </td>
        </tr>
        </tbody>
    </table>

    <table class="summary-table">
        <tr>
            <td width="25%"></td>

            <td class="bank-box" width="40%">
                @if(
                    (!empty($companyInfo['bank_name'])) ||
                    (!empty($companyInfo['beneficiary_name'])) ||
                    (!empty($companyInfo['beneficiary_address'])) ||
                    (!empty($companyInfo['account_number'])) ||
                    (!empty($companyInfo['routing_number'])) ||
                    (!empty($companyInfo['branch_address']))
                )
                    <strong>Bank Details:</strong><br>
                    @if(!empty($companyInfo['bank_name']))
                        Bank: {{ $companyInfo['bank_name'] }}<br>
                    @endif
                    @if(!empty($companyInfo['beneficiary_name']))
                        Beneficiary: {{ $companyInfo['beneficiary_name'] }}<br>
                    @endif
                    @if(!empty($companyInfo['beneficiary_address']))
                        Address: {!! nl2br(e($companyInfo['beneficiary_address'])) !!}<br>
                    @endif
                    @if(!empty($companyInfo['account_number']))
                        Account #: {{ $companyInfo['account_number'] }}<br>
                    @endif
                    @if(!empty($companyInfo['routing_number']))
                        Routing #: {{ $companyInfo['routing_number'] }}<br>
                    @endif
                    @if(!empty($companyInfo['branch_address']))
                        Branch Address: {!! nl2br(e($companyInfo['branch_address'])) !!}<br>
                    @endif
                @endif
            </td>

            <td width="35%" valign="top">
                <table class="total-table">
                    <tr>
                        <td>Subtotal:</td>
                        <td align="right">{{ number_format($order->price, 2) }}</td>
                    </tr>
                    <tr>
                        <td>Shipping:</td>
                        <td align="right">{{ number_format($order->shipping_cost, 2) }}</td>
                    </tr>
                    <tr>
                        <td>Tax (0%):</td>
                        <td align="right">0.00</td>
                    </tr>
                    <tr class="total-row">
                        <td>Total:</td>
                        <td align="right">
                            {{ number_format($order->price + $order->shipping_cost, 2) }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="footer">
        Thank you for your business!<br>
        {{ $companyInfo['name'] }} | {{ $companyInfo['email'] }}
    </div>

</div>

</body>
</html>