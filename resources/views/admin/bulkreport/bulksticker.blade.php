<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1"> 
  <style type="text/css">
    body {
      font-family: 'Times New Roman', Times, serif;
      margin: 0;
      padding: 0;
      overflow: visible; /* Allow content to show correctly */
      width: 4in; /* Width for printing */
      height: auto; /* Height for printing set to auto */
      padding-left: 0.7in; /* Add left margin */
      padding-top: 0.1in; /* Add top margin */
    }

    .mytable {
      border-collapse: collapse;
      width: 100%;
      background-color: white;  
    }
    .mytable tr, .mytable td {
      font-size: 10px; /* Adjust font size as needed */
      padding: 0; /* Remove padding to save space */
    }

    @media print {
      @page {
        size: 4in 6in; /* Set the page size to 4x6 inches */
        margin: 0; /* Remove default margins */
      }
      body {
        margin: 0; /* Ensure body has no margin */
        padding-left: 0.7in; /* Keep left margin for print */
        padding-top: 0.1in; /* Keep top margin for print */
      }
      .invoice {
        page-break-after: always; /* Each invoice starts on a new page */
        width: 3.8in; /* Set a specific width for invoice */
        height: auto; /* Set height for invoice to auto */
        break-after: page; /* Ensures it starts on a new page in modern browsers */
      }
      .invoice img {
        max-width: 60%; /* Ensure images fit within their container */
        height: auto; /* Maintain aspect ratio */
      }
    }

    .grid-container {
      display: flex; /* Use flex for layout */
      flex-direction: column; /* Stack invoices vertically */
      padding: 0; /* Remove any padding that may affect layout */
      margin: 0; /* Remove margin */
    }
  </style>
</head>
<body>
@php ($i = 0)
<div class="grid-container">
  @foreach ($datas as $data)
  @for ($r = 1; $r <= $data['pices']; $r++)
    <section class="invoice">
      <table class="mytable">
        <tr style="border-bottom: 1px solid #36454F;">
          <td colspan="2" style="font-weight: bold; text-align: center;">SB EXPRESS CARGO <strong style="font-size: 7px;">{{ $data['service_type'] }}</strong></td>
        </tr>
        <tr style="border-bottom: 1px solid #36454F;">
          <td><strong>SHIPPER COPY</strong> | CUST CODE: SBHFL01</td>
          <td>ORG:{{ $data['pickuplocation'] }} — DST: {{ $data['deliverylocation'] }}</td>
        </tr>
        <tr>
          <td><strong>Act Wgt:</strong>{{ strlen((string) $data['weight']) > 3 ? number_format($data['weight'] / 1000, 2) . ' Kg' : $data['weight'] . ' Kg' }}</td>
          <td><strong>Pcs:</strong>{{$r}} of {{ $data['pices'] }}</td>
        </tr>
        <tr style="border-bottom: 1px solid #36454F;">
    <td>
        <strong>Date:</strong>
        {{ !empty($data['booking_date']) ? \Carbon\Carbon::parse($data['booking_date'])->format('d/m/Y') : date('d/m/Y') }}
    </td>
    <td>
        <strong>DIMS#:</strong>
        {{ is_array($data['dimension']) ? ($data['dimension']['l'] . '*' . $data['dimension']['b'] . '*' . $data['dimension']['h']) : 'N/A' }}
    </td>
</tr>
        <tr style="border-bottom: 1px solid #36454F;">
    <td colspan="2" style="text-align: center;">
        <strong >{!! DNS1D::getBarcodeSVG($data['forwordingno'], 'C128', 1, 30) !!}</strong><br>
        <strong> {{ $data['delivery_type'] }}</strong><br>
    </td>
</tr>
        <tr>
          <td colspan="2"><strong>SENDER:</strong> SB Express Cargo | GST: 27ADGFS2635E1Z5</td>
        </tr>
        <tr style="border-bottom: 1px solid #36454F;">
          <td><strong>Inv No:</strong>{{ $data['invoice_no'] }} || <strong>Inv Val:</strong>{{ $data['value'] }} INR</td>
          <td><strong>Waybill No:</strong> {{ $data['waybills'] }} </td>
        </tr>
        <tr>
          <td colspan="2"><strong>RECEIVER:</strong>{{ $data['con_client_name'] }}</td>
        </tr>
        <tr>
          <td colspan="2">{{ $data['receiveraddress'] }}</td>
        </tr>
        <tr style="border-bottom: 1px solid #36454F;">
          <td><strong>Pincode:</strong> {{ $data['receiver_pincode'] }}</td>
          <td><strong>Ph:</strong> {{ $data['receivercontactno'] }}</td>
        </tr>
        <tr>
          <td colspan="2"><strong>CMDTY:</strong>{{ $data['content'] }}</td>
        </tr>
      </table>
    </section>
    
    @php ($i++)
    @endfor
  @endforeach
</div>

<script>
  window.onload = function() {
    setTimeout(function() {
      window.print();
    }, 1000);
  };
</script>
</body>
</html>
