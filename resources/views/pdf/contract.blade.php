<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Sales Agreement</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            line-height: 1.6;
            margin: 40px;
            color: #000;
        }

        h1,
        h2 {
            text-align: center;
        }

        .section {
            margin-top: 20px;
        }

        .signature {
            margin-top: 80px;
        }
        
        .header-logo {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .header-logo img {
            max-width: 200px;
            max-height: 100px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .signature-table td {
            padding-top: 20px;
            vertical-align: top;
        }
    </style>
</head>

<body>

    <div class="header-logo">
        @php
            $logo = \App\Models\Settings::get('dark_logo') ?: (\App\Models\Settings::get('logo') ?: \App\Models\Settings::get('white_logo'));
        @endphp
        @if($logo && file_exists(public_path($logo)))
            <img src="{{ public_path($logo) }}" alt="Logo">
        @endif
    </div>

    <h2>Sales Agreement</h2>

    <p>
        THIS SALES AGREEMENT (the "Agreement") is dated this {{ date('jS \of F, Y', strtotime($contractDate)) }} BETWEEN:
    </p>

    <p>
        <strong>{{ $companyInfo['name'] ?? 'RB Equipment Sales' }}</strong><br>
        {{ $companyInfo['address'] ?? '' }}<br>
        (the "Seller")
    </p>

    <p style="text-align:center;">- AND -</p>

    <p>
        <strong>{{ $user->first_name }} {{ $user->last_name }}</strong><br>
        {{ $user->address ?? '' }}{{ $user->city ? ', ' . $user->city : '' }}{{ $user->state ? ', ' . $user->state : '' }}{{ $user->zip_code ? ', ' . $user->zip_code : '' }}<br>
        (the "Buyer")
    </p>

    <p>
        IN CONSIDERATION of the covenants and agreements contained in this Agreement, the parties agree as follows:
    </p>

    <div class="section">
        <strong>1. Sale of Goods</strong><br>
        The Seller agrees to sell, transfer, and deliver to the Buyer the following goods within 14 days after the
        payment is processed:
        <br><br>
        {{ $machinery->year }} {{ $machinery->make }} {{ $machinery->model }}<br>
        Hours: {{ $machinery->working_hours }}<br>
        Serial No.: {{ $machinery->serial_number }}
    </div>

    <div class="section">
        <strong>2. Purchase Price</strong><br>
        The Buyer shall pay the sum of ${{ number_format($highestBid->amount ?? 0, 2) }} (the "Purchase Price") by bank wire transfer as required in Clause 5,
        which includes the following:
        <ul>
            <li>Equipment Cost: ${{ number_format($highestBid->amount ?? 0, 2) }}</li>
        </ul>

        The Seller and Buyer acknowledge the sufficiency of this consideration. Any applicable taxes will be paid by the
        Seller unless the Buyer provides a valid tax exemption certificate acceptable to the relevant taxing
        authorities.
    </div>

    <div class="section">
        <strong>3. Payment</strong><br>
        The Buyer commits to paying for the Goods in accordance with the terms of this Agreement.
    </div>

    <div class="section">
        <strong>4. Delivery of Goods</strong><br>
        The Goods will be deemed received by the Buyer when delivered to the provided delivery address. The Seller is
        fully responsible for the shipping process.
    </div>

    <div class="section">
        <strong>5. Risk of Loss</strong><br>
        The risk of loss or damage to the Goods remains with the Seller until the Goods are received by the Buyer. The
        Buyer shall provide insurance covering both parties' interests until full payment is made to the Seller.
    </div>

    <div class="section">
        <strong>6. Warranties</strong><br>
        The Goods are sold with a 6-month warranty on the engine and drivetrain. The Seller disclaims all other
        warranties, except any applicable manufacturer warranties.
    </div>

    <div class="section">
        <strong>7. Inspection</strong><br>
        The Buyer has a 30-day inspection period from the date of receipt. If the Goods are damaged or defective, the
        Buyer may return the Goods at the Seller's expense for a full refund.
    </div>

    <div class="section">
        <strong>8. Title and Documents</strong><br>
        The Seller shall deliver all necessary title documents to the Buyer with the Goods.
    </div>

    <div class="section">
        <strong>9. Security Interest</strong><br>
        The Seller retains the Purchase Price in escrow until the 30-day inspection period expires.
    </div>

    <div class="section">
        <strong>10. Claims</strong><br>
        Failure by the Buyer to notify the Seller of any claim within 35 days from delivery constitutes acceptance of
        the Goods and waives all claims.
    </div>

    <div class="section">
        <strong>11. Excuse for Delay or Failure</strong><br>
        The Seller is not liable for delays or defaults due to causes beyond its control, including labor disputes,
        transportation issues, or accidents. If delivery cannot occur within the agreed time, the Seller may terminate
        this Agreement with a full refund.
    </div>

    <div class="section">
        <strong>12. Remedies</strong><br>
        The Buyer's exclusive remedy for defective Goods or other losses is limited to the Purchase Price paid, plus any
        actual transportation charges.
    </div>

    <div class="section">
        <strong>13. Cancellation</strong>
        <ul>
            <li>The Buyer fails to pay for any shipment when due</li>
            <li>The Buyer becomes insolvent or bankrupt</li>
            <li>The Seller deems payment prospects impaired</li>
        </ul>
    </div>

    <div class="section">
        <strong>14. Notices</strong><br>
        Any notices under this Agreement shall be delivered personally or by prepaid registered mail to the addresses
        below:
        <br><br>
        Seller: {{ $companyInfo['name'] ?? '' }}, {{ $companyInfo['address'] ?? '' }}<br>
        Buyer: {{ $user->first_name }} {{ $user->last_name }}, {{ $user->address ?? '' }}{{ $user->city ? ', ' . $user->city : '' }}{{ $user->state ? ', ' . $user->state : '' }}{{ $user->zip_code ? ', ' . $user->zip_code : '' }}
    </div>

    <div class="section">
        <strong>15. General Provisions</strong>
        <ul>
            <li>Headings are for convenience and do not affect interpretation.</li>
            <li>All representations and warranties survive the closing of this Agreement.</li>
            <li>Neither party may assign obligations without written consent.</li>
            <li>Modifications must be in writing and signed by both parties.</li>
            <li>This Agreement is governed by the laws of Missouri and the Missouri Uniform Commercial Code.</li>
            <li>If any clause is unenforceable, the remainder remains in effect.</li>
            <li>This Agreement benefits and binds the parties and their successors.</li>
            <li>Execution in counterparts and facsimile signatures are valid.</li>
            <li>Time is of the essence.</li>
            <li>This Agreement constitutes the entire understanding between the parties.</li>
        </ul>
    </div>

    <div class="signature">
        <p><strong>IN WITNESS WHEREOF</strong></p>
        <p>The parties have executed this Sales Agreement as follows:</p>

        <br>

        <table class="signature-table">
            <tr>
                <td style="width: 50%;">
                    Buyer's Signature: <br>
                    @if(isset($absoluteSignaturePath) && file_exists($absoluteSignaturePath))
                        <img src="{{ $absoluteSignaturePath }}" alt="Buyer Signature" style="max-width: 200px; max-height: 60px;"><br>
                    @else
                        ____________________________<br>
                    @endif
                    Name: {{ $user->first_name }} {{ $user->last_name }}<br>
                    Date: {{ $contractDate }}
                </td>
                <td style="width: 50%;">
                    Seller's Signature: <br>
                    ____________________________<br>
                    Authorized Representative<br>
                    {{ $companyInfo['name'] ?? '' }}<br>
                    Date: {{ $contractDate }}
                </td>
            </tr>
        </table>

    </div>

</body>

</html>
