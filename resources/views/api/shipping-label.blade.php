<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style type="text/css">
        body {
            font-family: 'Times New Roman', Times, serif;
            margin: 0 auto;
            padding: 0;
            width: 3in;
            height: auto;
        }

        .mytable {
            border-collapse: collapse;
            width: 100%;
            background-color: white;
        }
        
        .mytable tr, .mytable td {
            font-size: 10px;
            padding: 3px 2px;
            vertical-align: top;
        }

        .barcode-container {
            text-align: center;
            margin: 4px 0;
        }

        .barcode-container img {
            max-width: 85%;
            height: auto;
        }

        .barcode-text {
            font-size: 10px;
            font-weight: bold;
            margin-top: 2px;
            display: block;
        }

        .invoice {
            width: 3in;
            height: 6in;
            margin: 0;
            padding: 0;
            display: block;
            page-break-inside: avoid;
        }

        @media print {
            @page {
                size: 3in 6in;
                margin: 0;
            }
            body {
                margin: 0 auto;
            }
            .invoice {
                width: 3in !important;
                height: 6in !important;
                margin: 0 !important;
                padding: 0 !important;
                display: block !important;
                page-break-inside: avoid !important;
                page-break-after: always !important;
            }
            .invoice:last-child {
                page-break-after: avoid !important;
            }
        }

        .grid-container {
            display: block;
            padding: 0;
            margin: 0;
        }

        .divider {
            border-bottom: 1px solid #36454F;
        }
    </style>
</head>
<body>
    <div class="grid-container">
        @php
            $totalPieces = $booking->pices ?? 1;
            $dims = $booking->dimension;
        @endphp
        
        @for($i = 1; $i <= $totalPieces; $i++)
            <section class="invoice">
                <table class="mytable">
                    <tr class="divider">
                        <td colspan="2" style="font-weight: bold; text-align: center; font-size: 12px;">
                            SB EXPRESS CARGO <br>
                            <strong style="font-size: 9px;">{{ $booking->service_type ?? 'STANDARD' }}</strong>
                        </td>
                    </tr>
                    <tr class="divider">
                        <td><strong>SHIPPER COPY</strong> | CUST CODE: SBHFL01</td>
                        <td>ORG: {{ $booking->pickuplocation ?? 'Mumbai' }} — DST: {{ $booking->deliverylocation ?? $booking->receivercity }}</td>
                    </tr>
                    <tr>
                        <td><strong>Act Wgt:</strong> {{ $booking->weight ?? 0 }} g</td>
                        <td><strong>Pcs:</strong> {{ $i }} of {{ $totalPieces }}</td>
                    </tr>
                    <tr class="divider">
                        <td><strong>Date:</strong> {{ $booking->booking_date ? \Carbon\Carbon::parse($booking->booking_date)->format('d/m/Y') : date('d/m/Y') }}</td>
                        <td><strong>DIMS#:</strong> {{ is_array($dims) ? ($dims['l'] . '*' . $dims['b'] . '*' . $dims['h']) : 'N/A' }}</td>
                    </tr>
                    <tr class="divider">
                        <td colspan="2" class="barcode-container">
                            <img src="data:image/png;base64,{{ DNS1D::getBarcodePNG($booking->forwordingno, 'C128', 1.5, 40) }}" alt="barcode" />
                            <span class="barcode-text">{{ $booking->forwordingno }}</span>
                            <strong>{{ $booking->delivery_type ?? 'NORMAL' }}</strong>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2"><strong>SENDER:</strong> SB Express Cargo | GST: 27ADGFS2635E1Z5</td>
                    </tr>
                    <tr class="divider">
                        <td><strong>Inv No:</strong> {{ $booking->invoice_no ?? 'N/A' }} <br><strong>Inv Val:</strong> {{ $booking->value ?? 0 }} INR</td>
                        <td><strong>Waybill No:</strong><br><strong style="font-size:12px;">{{ $booking->waybills ?? $booking->forwordingno }}</strong></td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <strong>RECEIVER:</strong><br>
                            <span style="font-size:11px; font-weight:bold;">{{ $booking->con_client_name ?? $booking->receivername ?? 'N/A' }}</span><br>
                            {{ $booking->receiveraddress ?? 'N/A' }}
                        </td>
                    </tr>
                    <tr class="divider">
                        <td><strong>Pincode:</strong> {{ $booking->receiver_pincode ?? 'N/A' }}</td>
                        <td><strong>Ph:</strong> {{ $booking->receivercontactno ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td colspan="2"><strong>CMDTY:</strong> {{ $booking->content ?? 'N/A' }}</td>
                    </tr>
                </table>
            </section>
        @endfor
    </div>
</body>
</html>
