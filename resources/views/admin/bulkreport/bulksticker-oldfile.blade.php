<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1"> 
  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="../../plugins/fontawesome-free/css/all.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="../../dist/css/adminlte.min.css">
  <style type="text/css">
    body {
      font-family: 'Times New Roman', Times, serif;
    }  
    .mytable {
      border-collapse: collapse;
      width: 100%;
      background-color: white;
      
    }
    .mytable tr, .mytable td {
      
      font-size: 10px; 
      padding: 1px;
    }
    @media print {
      .pagebreak { page-break-before: always; }
    }
    .grid-container {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 20px;
    }
    .invoice {
      border: 1px solid #ccc;
      padding: 10px;
      box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.1);
    }
  </style>
</head>
<body>
@php ($i = 0)
<div class="grid-container">
  @foreach ($datas as $data)
    <section class="invoice">
      <table class="mytable">
        <tr style="border-bottom: 1px solid #36454F;">
          <td colspan="2" style="font-weight: bold; text-align: center; font-size: 12px;">SB EXPRESS CARGO</td>
        </tr>
        <tr style="border-bottom: 1px solid #36454F;">
          <td><strong>SHIPPER COPY</strong> | REF NO: {{ $data['ref_no'] }}</td>
          <td>ORG:Mumbai — DST: Surat</td>
        </tr>
        <tr>
          <td><strong>Act Wgt:</strong> 10 KGS</td>
          <td><strong>Pcs:</strong> 2</td>
        </tr>
        <tr style="border-bottom: 1px solid #36454F;">
          <td><strong>Date:</strong> 12/8/2024</td>
          <td><strong>EMP#:</strong> 12216</td>
        </tr>
        
        <tr style="border-bottom: 1px solid #36454F;">
          <td colspan="2" style="text-align: center;"><img src="{{ route('BarcodeIMG',$data->forwordingno) }}" alt="Barcode"><br><strong>12834</strong><br><strong>NORMAL</strong></td>
        </tr>
       
        <tr>
          <td colspan="2"><strong>SENDER:</strong> SB Cargo | GST: 12BSU884JD</td>
        </tr>
        <tr style="border-bottom: 1px solid #36454F;">
          <td><strong>Inv No:</strong> 001</td>
          <td><strong>Inv Val:</strong> 5000.00 INR</td>
        </tr>
        <tr>
          <td colspan="2"><strong>RECEIVER:</strong> Surat Mills</td>
        </tr>
        <tr>
          <td colspan="2">Surat</td>
        </tr>
        <tr style="border-bottom: 1px solid #36454F;">
          <td><strong>Pincode:</strong> 401208</td>
          <td><strong>Ph:</strong> 8434567890</td>
        </tr>
        <tr>
          <td colspan="2"><strong>CMDTY:</strong> Gift Item</td>
        </tr>
       
      </table>
    </section>
    
   

    @php ($i++)
  @endforeach
</div>

<!-- Page specific script -->
<script>
  setTimeout(function() {
    window.print();
  }, 1000);
</script>
</body>
</html>
