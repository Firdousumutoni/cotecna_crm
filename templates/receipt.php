<?php
if (!isset($_GET['id'])) die("Invoice ID required");

// Fetch Invoice & Client
$stmt = $pdo->prepare("SELECT i.*, c.company, c.email, c.phone FROM invoices i JOIN clients c ON i.client_id = c.id WHERE i.id = ?");
$stmt->execute([$_GET['id']]);
$inv = $stmt->fetch();

if (!$inv) die("Invoice not found");

// Calculations (Assuming Amount is Grand Total)
$grand_total = $inv['amount'];
$vat_rate = 0.16;
$subtotal = $grand_total / (1 + $vat_rate);
$vat_amount = $grand_total - $subtotal;

// Number to Words Function (Simple Version)
function numberToWords($number) {
    $hyphen      = '-';
    $conjunction = ' and ';
    $separator   = ', ';
    $negative    = 'negative ';
    $decimal     = ' point ';
    $dictionary  = [
        0 => 'zero', 1 => 'one', 2 => 'two', 3 => 'three', 4 => 'four', 5 => 'five', 6 => 'six', 
        7 => 'seven', 8 => 'eight', 9 => 'nine', 10 => 'ten', 11 => 'eleven', 12 => 'twelve', 
        13 => 'thirteen', 14 => 'fourteen', 15 => 'fifteen', 16 => 'sixteen', 17 => 'seventeen', 
        18 => 'eighteen', 19 => 'nineteen', 20 => 'twenty', 30 => 'thirty', 40 => 'forty', 
        50 => 'fifty', 60 => 'sixty', 70 => 'seventy', 80 => 'eighty', 90 => 'ninety',
        100 => 'hundred', 1000 => 'thousand', 1000000 => 'million', 1000000000 => 'billion'
    ];

    if (!is_numeric($number)) return false;
    // Handle decimals
    if (($number >= 0 && (int) $number < 0) || (int) $number < 0 - PHP_INT_MAX) {
        // overflow
        trigger_error('numberToWords only accepts numbers between -' . PHP_INT_MAX . ' and ' . PHP_INT_MAX, E_USER_WARNING);
        return false;
    }

    if ($number < 0) return $negative . numberToWords(abs($number));

    $string = $fraction = null;
    if (strpos($number, '.') !== false) {
        list($number, $fraction) = explode('.', $number);
    }

    switch (true) {
        case $number < 21: $string = $dictionary[$number]; break;
        case $number < 100:
            $tens   = ((int) ($number / 10)) * 10;
            $units  = $number % 10;
            $string = $dictionary[$tens];
            if ($units) $string .= $hyphen . $dictionary[$units];
            break;
        case $number < 1000:
            $hundreds  = $number / 100;
            $remainder = $number % 100;
            $string = $dictionary[$hundreds] . ' ' . $dictionary[100];
            if ($remainder) $string .= $conjunction . numberToWords($remainder);
            break;
        default:
            $baseUnit = pow(1000, floor(log($number, 1000)));
            $numBaseUnits = (int) ($number / $baseUnit);
            $remainder = $number % $baseUnit;
            $string = numberToWords($numBaseUnits) . ' ' . $dictionary[$baseUnit];
            if ($remainder) $string .= $remainder < 100 ? $conjunction . numberToWords($remainder) : $separator . numberToWords($remainder);
            break;
    }
    return ucfirst($string);
}

$amount_words = numberToWords(floor($grand_total)) . ' Shillings Only';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt #<?php echo $inv['invoice_number']; ?></title>
    <style>
        :root {
            --brand-red: #D80E2B;
            --brand-blue: #0C2B5F;
            --brand-light: #f1f5f9;
        }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f1f5f9; padding: 40px; font-size: 14px; color: #334155; }
        .receipt-container {
            max-width: 800px; margin: 0 auto; background: white; padding: 0; 
            border: 1px solid #cbd5e1; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
            border-top: 5px solid var(--brand-red);
            border-radius: 8px; overflow: hidden;
        }
        .box { padding: 20px 30px; border-bottom: 1px solid #e2e8f0; }
        .box:last-child { border-bottom: none; }
        
        .header-section { text-align: center; padding: 30px 30px 10px; }
        .company-name { font-size: 2rem; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 1px; color: var(--brand-blue); font-weight: 800;}
        .sub-title { font-size: 1rem; margin-bottom: 15px; font-style: italic; color: #64748b; }
        .company-details { font-size: 0.9rem; line-height: 1.5; color: #475569; }
        
        .receipt-title { 
            text-align: center; font-weight: 700; font-size: 1.2rem; 
            padding: 10px 0; background: var(--brand-blue); color: white;
            text-transform: uppercase; letter-spacing: 2px;
            margin-top: 20px;
        }

        .info-row { display: flex; justify-content: space-between; margin-bottom: 8px; }
        .label { font-weight: 700; width: 150px; color: #64748b; text-transform: uppercase; font-size: 0.8rem; }
        .value { flex: 1; font-weight: 600; color: #0f172a; }

        .section-title { 
            font-size: 0.85rem; font-weight: 700; color: var(--brand-red); 
            text-transform: uppercase; letter-spacing: 1px; margin-bottom: 15px; 
            border-bottom: 2px solid #e2e8f0; padding-bottom: 5px; display: inline-block;
        }

        .payment-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .payment-table th, .payment-table td { padding: 12px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        .payment-table th { background: #f8fafc; color: #64748b; font-weight: 600; text-transform: uppercase; font-size: 0.8rem; border-top: 2px solid var(--brand-blue); }
        .text-right { text-align: right !important; }

        .total-row { background: var(--brand-light); font-weight: 700; color: var(--brand-blue); font-size: 1.1rem; }
        .amount-words { margin-top: 20px; font-style: italic; color: #475569; background: #fffbeb; padding: 10px; border-left: 3px solid #f59e0b; }

        .footer-sign { margin-top: 40px; display: flex; justify-content: space-between; align-items: flex-end; }
        .sign-box { border-top: 2px solid #cbd5e1; width: 250px; text-align: center; padding-top: 10px; font-weight: 600; color: #475569; }

        @media print {
            body { background: white; padding: 0; }
            .receipt-container { box-shadow: none; border: 1px solid #ccc; width: 100%; max-width: 100%; border-radius: 0; }
            .no-print { display: none; }
            .receipt-title { background: #0C2B5F !important; color: white !important; -webkit-print-color-adjust: exact; }
            .section-title { color: #D80E2B !important; }
            .total-row { background: #f1f5f9 !important; -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <!-- Header -->
        <div class="box" style="padding-bottom: 0;">
            <div class="header-section">
                <div class="company-name">Cotecna Kenya Limited</div>
                <div class="sub-title">Testing, Inspection and Certification Services</div>
                
                <div class="company-details">
                    Head Office: JKIA Cargo Centre, Mombasa Road<br>
                    P.O. Box 62526 - 00200, Nairobi, Kenya<br>
                    Tel: +254 20 123 4567 | Email: info.kenya@cotecna.com<br>
                    KRA PIN: P051234567Y &nbsp;&nbsp; VAT No: 012345678T
                </div>
            </div>
            <div class="receipt-title">Official Receipt</div>
        </div>

        <!-- Receipt Meta -->
        <div class="box">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div style="flex:1;">
                    <div class="info-row"><div class="label">Receipt No:</div><div class="value">R-CTN-<?php echo date('Ymd', strtotime($inv['created_at'])); ?>-<?php echo $inv['id']; ?></div></div>
                    <div class="info-row"><div class="label">Date:</div><div class="value"><?php echo date('d-M-Y', strtotime($inv['issue_date'])); ?></div></div>
                    <div class="info-row"><div class="label">Currency:</div><div class="value">Kenya Shillings (KES)</div></div>
                </div>
                <div style="text-align: right;">
                     <div style="border: 2px solid var(--brand-red); color: var(--brand-red); padding: 5px 15px; font-weight: 700; text-transform: uppercase; border-radius: 4px; display: inline-block;">
                        <?php echo ($inv['status'] === 'Paid') ? 'ORIGINAL' : 'COPY'; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Received From -->
        <div class="box">
            <div class="section-title">Received From</div>
            <div style="font-weight: 700; font-size: 1.1rem; color: var(--brand-blue);"><?php echo htmlspecialchars($inv['company']); ?></div>
            <div style="color: #475569;">[Address Placeholder Box 1234]</div>
            <div style="color: #475569;">[Nairobi, Kenya]</div>
            <div style="margin-top: 5px; font-size: 0.9rem;"><strong>PIN:</strong> A00<?php echo rand(100000, 999999); ?>Z</div>
        </div>

        <!-- Details -->
        <div class="box">
            <div class="section-title">Details of Payment</div>
            <div style="font-size: 1.05rem;"><?php echo htmlspecialchars($inv['description']); ?></div>
            <div style="font-style: italic; color: #94a3b8; margin-top: 5px; font-size: 0.9rem;">Related to Invoice #<?php echo $inv['invoice_number']; ?></div>
        </div>

        <!-- Financial Breakdown -->
        <div class="box">
            <div class="section-title">Financial Breakdown</div>
            <table class="payment-table">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th class="text-right">Amount (KES)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Service Charges</td>
                        <td class="text-right"><?php echo number_format($subtotal, 2); ?></td>
                    </tr>
                    <tr>
                        <td style="color:#94a3b8; font-size:0.8rem;"><i>Taxable Amount</i></td>
                        <td class="text-right" style="color:#94a3b8; font-size:0.8rem;"><i><?php echo number_format($subtotal, 2); ?></i></td>
                    </tr>
                    <tr>
                        <td>VAT (16%)</td>
                        <td class="text-right"><?php echo number_format($vat_amount, 2); ?></td>
                    </tr>
                    <tr class="total-row">
                        <td>GRAND TOTAL PAID</td>
                        <td class="text-right"><?php echo number_format($grand_total, 2); ?></td>
                    </tr>
                </tbody>
            </table>

            <div class="amount-words">
                <strong>Amount in Words:</strong> <?php echo $amount_words; ?>
            </div>
        </div>

        <!-- Payment Method -->
        <div class="box">
            <div class="section-title">Payment Method</div>
            <div style="display: flex; gap: 30px; font-weight: 600; color: #475569;">
                <div><span style="color: var(--brand-blue);"><i class="fa-solid fa-square-check"></i></span> Bank Transfer</div>
                <div><span style="color: #cbd5e1;"><i class="fa-regular fa-square"></i></span> Cheque</div>
                <div><span style="color: #cbd5e1;"><i class="fa-regular fa-square"></i></span> Mobile Money</div>
            </div>
        </div>

        <!-- Footer -->
        <div class="box" style="border-bottom: none; background: #f8fafc;">
            <div style="margin-bottom: 50px; margin-top: 10px; font-size: 0.9rem;">
                <span style="color: #64748b;">Served By:</span> <strong><?php echo $_SESSION['user_name'] ?? 'System Admin'; ?></strong>
            </div>

            <div class="footer-sign">
                <div class="sign-box">
                    Authorized Signature & Stamp
                </div>
                <div style="font-size: 0.75rem; font-style: italic; color: #94a3b8;">
                    *This is a computer-generated document. No signature required.*
                </div>
            </div>
        </div>

        <div class="no-print" style="margin: 30px 0; text-align: center;">
            <button onclick="window.print()" style="padding: 12px 25px; background: var(--brand-blue); color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 700; font-family: inherit; display: inline-flex; align-items: center; gap: 8px;">
                PRINT RECEIPT
            </button>
        </div>
    </div>
</body>
</html>
