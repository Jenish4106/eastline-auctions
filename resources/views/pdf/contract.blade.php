<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>Sales Agreement</title>
    <style>
        @page {
            margin: 0.8cm 1.2cm;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 10pt;
            line-height: 1.3;
            color: #000;
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            margin-bottom: 10px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .logo {
            max-height: 50px;
            margin-bottom: 5px;
        }
        .company-info {
            font-size: 9pt;
            color: #333;
            margin-bottom: 5px;
        }
        .header-title {
            font-size: 18pt;
            font-weight: bold;
            color: #000;
            margin: 5px 0 2px 0;
        }
        .header-subtitle {
            font-size: 9pt;
            color: #666;
            margin: 0;
        }
        .section-title {
            font-size: 11pt;
            font-weight: bold;
            margin-top: 12px;
            margin-bottom: 5px;
            color: #000;
        }
        .content {
            margin-bottom: 8px;
            text-align: left;
        }
        .parties-section {
            margin: 15px 0 20px 0;
        }
        .party-block {
            margin-bottom: 10px;
            display: inline-block;
            width: 48%;
            vertical-align: top;
        }
        .party-label {
            font-weight: bold;
            font-size: 9pt;
            color: #333;
            margin-bottom: 2px;
        }
        .party-info {
            font-size: 9pt;
            line-height: 1.4;
        }
        .center-text {
            text-align: center;
            font-weight: bold;
            margin: 5px 0;
            font-size: 10pt;
        }
        .bold {
            font-weight: bold;
        }
        ul {
            margin: 5px 0 8px 20px;
            padding: 0;
            list-style-type: disc;
        }
        li {
            margin-bottom: 2px;
        }
        .footer {
            margin-top: 20px;
        }
        .signature-row {
            margin-top: 20px;
        }
        .signature-line {
            display: inline-block;
            border-bottom: 1px solid #000;
            width: 300px;
            margin-left: 10px;
            height: 25px;
            vertical-align: bottom;
            position: relative;
        }
        .signature-name {
            font-family: 'DejaVu Sans', sans-serif;
            font-style: italic;
            font-size: 12pt;
            color: #000;
            position: absolute;
            bottom: 2px;
            left: 5px;
        }
        .signature-img {
            max-height: 40px;
            position: absolute;
            bottom: -5px;
            left: 5px;
        }
        .page-break {
            page-break-after: always;
        }
        .agreement-intro {
            text-align: center;
            font-weight: bold;
            margin: 10px 0 10px 0;
            font-size: 10pt;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 5px 0;
        }
        td {
            padding: 5px;
            vertical-align: top;
        }
        .address-label {
            font-weight: bold;
            width: 30%;
        }
        .address-content {
            font-size: 9pt;
        }
    </style>
</head>
<body>
    <!-- Page 1 -->
        <div class="header">
        @if(isset($companyInfo['logo']) && !empty($companyInfo['logo']))
            <img src="{{ $companyInfo['logo'] }}" class="logo" alt="Company Logo">
        @elseif(isset($companyInfo['logoUrl']) && !empty($companyInfo['logoUrl']))
            <img src="{{ $companyInfo['logoUrl'] }}" class="logo" alt="Company Logo">
        @endif
        <div class="header-title">{{ $companyInfo['name'] }}</div>
        <div class="company-info">
            {{ $companyInfo['address'] }}<br>
            {{ $companyInfo['phone'] }} | {{ $companyInfo['email'] }}
        </div>
    </div>

    <div class="header-title" style="text-align: center; font-size: 16pt; margin-bottom: 10px;">SALES AGREEMENT</div>

    <div class="agreement-intro">
        THIS SALES AGREEMENT (the "Agreement") is dated this {{ \Carbon\Carbon::parse($contractDate)->format('jS \o\f F, Y') }}
    </div>

    <div class="parties-section">
        <div class="center-text" style="margin-bottom: 10px;">BETWEEN:</div>

        <table>
            <tr>
                <td class="party-label address-label">SELLER:</td>
                <td class="address-content">
                    <strong>{{ $companyInfo['name'] }}</strong><br>
                    {{ $sellerAddress ?? $companyInfo['address'] }}<br>
                    Phone: {{ $companyInfo['phone'] }}<br>
                    Email: {{ $companyInfo['email'] }}
                </td>
            </tr>
        </table>

        <div class="center-text" style="margin: 8px 0;">AND</div>

        <table>
            <tr>
                <td class="party-label address-label">BUYER:</td>
                <td class="address-content">
                    <strong>{{ $user->first_name }} {{ $user->last_name }}</strong><br>
                    {{ $buyerAddress ?? $user->email }}<br>
                    Phone: {{ $user->phone_no ?? '' }}<br>
                    Email: {{ $user->email }}
                </td>
            </tr>
        </table>
    </div>

    <div class="content" style="margin-top: 15px; text-align: center; font-weight: bold;">
        IN CONSIDERATION of the covenants and agreements contained in this Agreement, the parties agree as follows:
    </div>

    <div class="section-title">1. Sale of Goods</div>
    <div class="content">
        The Seller agrees to sell, transfer, and deliver to the Buyer the following goods within 10–14 days after the payment is processed:
        <ul>
            <li class="bold">{{ trim($machinery->year . ' ' . $machinery->make . ' ' . $machinery->model) }}</li>
            <li>Hours: {{ $machinery->working_hours }}</li>
            <li>Serial No.: {{ $machinery->serial_number }}</li>
        </ul>
    </div>

    <div class="section-title">2. Purchase Price & Delivery Cost</div>
        <div class="content">
            @php
                $equipmentCost = (
                    isset($is_checkout) && $is_checkout == true
                )
                    ? ($buy_now_price ?? 0)
                    : ($highestBid->amount ?? 0);

                $deliveryCost = $shipping_cost
                    ? $shipping_cost
                    : ($shipping_cost ?? 0);

                $totalAmount = $equipmentCost + $deliveryCost;
            @endphp

            The Buyer shall pay the sum of 
            <span class="bold">${{ number_format($totalAmount, 2) }}</span> 
            (the "Purchase Price") by bank wire transfer as required in Clause 5, which includes the following:

            <table>
                <tr>
                    <td style="width: 50%;">Equipment Cost:</td>
                    <td style="font-weight: bold;">
                        ${{ number_format($equipmentCost, 2) }}
                    </td>
                </tr>

                <tr>
                    <td>Delivery Cost:</td>
                    <td style="font-weight: bold;">
                        ${{ number_format($deliveryCost, 2) }}
                    </td>
                </tr>

                <tr style="border-top: 1px solid #000; font-weight: bold;">
                    <td>Total Amount:</td>
                    <td style="font-weight: bold;">
                        ${{ number_format($totalAmount, 2) }}
                    </td>
                </tr>
            </table>
        </div>
    <div class="content">
        The Seller and Buyer acknowledge the sufficiency of this consideration. Any applicable taxes will be paid by the Seller unless the Buyer provides a valid tax exemption certificate acceptable to the relevant taxing authorities.
    </div>

    <div class="section-title">3. Payment</div>
    <div class="content">
        The Buyer commits to paying for the Goods in accordance with the terms of this Agreement.
    </div>

    <div class="page-break"></div>

    <!-- Page 2 -->
    <div class="section-title">4. Delivery of Goods</div>
    <div class="content">
        The Goods will be delivered to the following address:
        <table>
            <tr>
                <td class="address-label">Shipping Address:</td>
                <td class="address-content">
                    {{ $shippingAddress ?? $buyerAddress }}<br>
                </td>
            </tr>
        </table>
        The Goods will be deemed received by the Buyer when delivered to the above address. The Seller is fully responsible for the shipping process.
    </div>

    <div class="section-title">5. Risk of Loss</div>
    <div class="content">
        The risk of loss or damage to the Goods remains with the Seller until the Goods are received by the Buyer. The Buyer shall provide insurance covering both parties' interests until full payment is made to the Seller.
    </div>

    <div class="section-title">6. Warranties</div>
    <div class="content">
        The Goods are sold with a <span class="bold">6-month warranty on the engine and drivetrain</span>. The Seller disclaims all other warranties, except any applicable manufacturer warranties.
    </div>

    <div class="section-title">7. Inspection</div>
    <div class="content">
        The Buyer has a 30-day inspection period from the date of receipt. If the Goods are damaged or defective, the Buyer may return the Goods at the Seller's expense for a full refund.
    </div>

    <div class="section-title">8. Title and Documents</div>
    <div class="content">
        The Seller shall deliver all necessary title documents to the Buyer with the Goods.
    </div>

    <div class="section-title">9. Security Interest</div>
    <div class="content">
        The Seller retains the Purchase Price in escrow until the 30-day inspection period expires.
    </div>

    <div class="section-title">10. Claims</div>
    <div class="content">
        Failure by the Buyer to notify the Seller of any claim within 35 days from delivery constitutes acceptance of the Goods and waives all claims.
    </div>

    <div class="section-title">11. Excuse for Delay or Failure</div>
    <div class="content">
        The Seller is not liable for delays or defaults due to causes beyond its control, including labor disputes, transportation issues, or accidents. If delivery cannot occur within the agreed time, the Seller may terminate this Agreement with a full refund.
    </div>

    <div class="section-title">12. Remedies</div>
    <div class="content">
        The Buyer's exclusive remedy for defective Goods or other losses is limited to the Purchase Price paid, plus any actual transportation charges.
    </div>

    <div class="page-break"></div>

    <!-- Page 3 -->
    <div class="section-title">13. Cancellation</div>
    <div class="content">
        The Seller may cancel this Agreement if:
        <ul>
            <li>The Buyer fails to pay for any shipment when due</li>
            <li>The Buyer becomes insolvent or bankrupt</li>
            <li>The Seller deems payment prospects impaired</li>
        </ul>
    </div>

    <div class="section-title">14. Notices</div>
    <div class="content">
        Any notices under this Agreement shall be delivered personally or by prepaid registered mail to the addresses below:
        <div style="margin-top: 10px;">
            <div class="bold">Seller: <span style="font-weight: normal;">{{ $companyInfo['name'] }}, {{ $companyInfo['address'] }}</span></div>
            <div class="bold" style="margin-top: 5px;">Buyer: <span style="font-weight: normal;">{{ $user->first_name }} {{ $user->last_name }}, Email: {{ $user->email }} {{ $user->phone_no ? ', Phone: ' . $user->phone_no : '' }}</span></div>
        </div>
    </div>

    <div class="section-title">15. General Provisions</div>
    <div class="content">
        <ul>
            <li>Headings are for convenience and do not affect interpretation.</li>
            <li>All representations and warranties survive the closing of this Agreement.</li>
            <li>Neither party may assign obligations without written consent.</li>
            <li>Modifications must be in writing and signed by both parties.</li>
            <li>This Agreement is governed by the laws of Colorado and the Colorado Uniform Commercial Code.</li>
            <li>If any clause is unenforceable, the remainder remains in effect.</li>
            <li>This Agreement benefits and binds the parties and their successors.</li>
            <li>Execution in counterparts and facsimile signatures are valid.</li>
            <li>Time is of the essence.</li>
            <li>This Agreement constitutes the entire understanding between the parties.</li>
        </ul>
    </div>

    <div class="footer">
        <div class="bold" style="font-size: 11pt; margin-bottom: 8px;">IN WITNESS WHEREOF</div>
        <div class="content">The parties have executed this Sales Agreement as follows:</div>

        <div class="signature-row" style="margin-top: 15px;">
            <span class="bold">Buyer's Signature:</span>
            <div class="signature-line">
                @if(isset($absoluteSignaturePath) && !empty($absoluteSignaturePath))
                    <img src="{{ $absoluteSignaturePath }}" class="signature-img">
                @elseif(isset($signaturePath) && !empty($signaturePath))
                    <img src="{{ Str::startsWith($signaturePath, 'http') ? $signaturePath : asset('public/' . ltrim($signaturePath, '/')) }}" class="signature-img">
                @else
                    <span class="signature-name">{{ $user->first_name }} {{ $user->last_name }}</span>
                @endif
            </div>
        </div>

        <div class="signature-row" style="margin-top: 15px;">
            <span class="bold">Seller's Signature:</span> <span style="margin-left: 5px;">{{ $companyInfo['name'] }}</span>
            <div class="signature-line">
                @if(isset($companyInfo['signature_path']) && !empty($companyInfo['signature_path']))
                    <img src="{{ $companyInfo['signature_path'] }}" class="signature-img">
                @else
                    <img src="{{ asset('public/uploads/signatures/seller_signature.png') }}" class="signature-img">
                @endif
            </div>
        </div>
    </div>
</body>
</html>
