 @extends('admin.layouts.tabelapp', ['activePage' => 'dashboard', 'titlePage' => __('Dashboard')])

@section('content')
    <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Couriers</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Couriers</li>
            </ol>
          </div>
        </div>
      </div><!-- /.container-fluid -->
    </section>

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="row">

            <div class="col-md-12">
            <!-- general form elements -->
            <div class="card card-primary">
              <div onclick="toggleDiv()" class="card-header">
                <h3 class="card-title">Manual Booking</h3>
              </div>
              <!-- /.card-header -->
              <!-- form start -->
           
                    <div id="ouickform" style="display: none;">
              <form method="POST" action="{{route('Booking.store')}}">
                @csrf
                <div class="card-body">
                    <div class="row">
                    <div class="col-md-6">
                    <div class="form-group">
                    <label for="booking_office">Booking Office</label>
                    <input type="text" class="form-control" name="booking_office"  id="booking_office" required>
                  </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                    <label for="booking_date">Booking Date&Time</label>
                    <input type="datetime-local" class="form-control" name="booking_date"  id="booking_date" required>
                  </div>
                  </div>
                    </div>
                    <div class="row">
                    <div class="col-md-4">
                     <div class="card card-primary">
              <div class="card-header" style="background-color: #fff;color: black;">
                <h3 class="card-title" style="
    color: #d13d11;
    font-weight: 800;
">AWS Details</h3>
              </div>   
              <div class="card-body">
                  <div class="form-group">
                    <label for="cust_name" >Customer Name</label>
                    <input type="text" class="form-control" name="cust_name"  id="cust_name" placeholder="Enter Customer name" required>
                  </div>
                  <div class="form-group">
                    <label for="forwordingno">AWB No.</label>
                    <input type="text" class="form-control" name="forwordingno"  id="forwordingno" placeholder="Enter AWB No" required>
                  </div>
                  <div class="form-group">
                    <label for="forwordingno">Refrence No.</label>
                    <input type="text" class="form-control" name="refrenceno"  id="refrenceno" placeholder="Enter refrenceno" required>
                  </div>
                    <div class="row">
                    <div class="col-md-6">
                       <div class="form-group">
                    <label for="pickuplocation">Pickup Location</label>
                    <input type="text" class="form-control" name="pickuplocation"  id="pickuplocation" placeholder="Enter Pickup Location" required>
                  </div>
                    </div>
                    <div class="col-md-6">
                       <div class="form-group">
                    <label for="deliverylocation">Delivery Location</label>
                    <input type="text" class="form-control" name="deliverylocation"  id="deliverylocation" placeholder="Enter Delivery Location" required>
                  </div>
                    </div> 
                  </div>
                 <div class="row">
                    <div class="col-md-6">
                       <div class="form-group">
                    <label for="product_type">Product Type</label>
                    <input type="text" class="form-control" name="product_type"  id="product_type" placeholder="Enter Product type" >
                  </div>
                    </div>
                    <div class="col-md-6">
                       <div class="form-group">
                    <label for="deliverylocation">Content</label>
                    <input type="text" class="form-control" name="content"  id="content" placeholder="Enter content">
                  </div>
                    </div> 
                  </div>
                 
                  
                    <div class="row">
                    <div class="col-md-3">
                      <div class="form-group">
                    <label for="weight">Weight</label>
                    <input type="text" class="form-control" name="weight"  id="weight" placeholder="Enter weight" >
                  </div> 
                    </div>
                    <div class="col-md-3">
                      <div class="form-group">
                    <label for="pices">Number of Boxes/Pieces</label>
                    <input type="number" class="form-control" name="pices"  id="pices" placeholder="Enter number of boxes/pieces" min="1" >
                  </div> 
                    </div>
                    <div class="col-md-3">
                      <div class="form-group">
                    <label for="charg_weight">Chargable Weight</label>
                    <input type="text" class="form-control" name="charg_weight"  id="charg_weight" placeholder="Enter Chargable Weight" >
                  </div> 
                    </div>
                    <div class="col-md-3">
                      <div class="form-group">
                    <label for="vol_weight">Vol Weight (Auto) in gram</label>
                    <input type="text" class="form-control" name="vol_weight"  id="vol_weight" placeholder="Auto calculated" readonly >
                  </div> 
                    </div>
                  </div>
                  <div class="row">
                    <div class="col-md-4">
                      <div class="form-group">
                    <label for="length">Length (cm)</label>
                    <input type="number" class="form-control" name="length"  id="length" placeholder="Enter length" step="0.01" min="0" >
                  </div> 
                    </div>
                    <div class="col-md-4">
                      <div class="form-group">
                    <label for="breadth">Breadth (cm)</label>
                    <input type="number" class="form-control" name="breadth"  id="breadth" placeholder="Enter breadth" step="0.01" min="0" >
                  </div> 
                    </div>
                    <div class="col-md-4">
                      <div class="form-group">
                    <label for="height">Height (cm)</label>
                    <input type="number" class="form-control" name="height"  id="height" placeholder="Enter height" step="0.01" min="0" >
                  </div> 
                    </div>
                  </div>
                  </div>
                  </div>
                </div>
                <div class="col-md-4">
                     <div class="card card-primary">
              <div class="card-header" style="background-color: #fff;color: black;">
                <h3 class="card-title" style="
    color: #d13d11;
    font-weight: 800;
">Consignor Details</h3>
              </div>   
              <div class="card-body">
                    <div class="form-group">
                    <label for="client_name">Sender Name</label>
                    <input type="text" class="form-control" name="client_name"  id="client_name" placeholder="Enter Client name" required>
                  </div>
                  <div class="form-group">
                    <label for="pickupaddress">Pickup Address</label>
                    <input type="text" class="form-control" name="pickupaddress"  id="pickupaddress" placeholder="Enter Pickup Address" required>
                  </div>
                  <div class="form-group">
                    <label for="client_name">Pickup State</label>
                    <input type="text" class="form-control" name="pickup_name"  id="pickup_name" placeholder="Enter Pickup State" required>
                  </div>
                  <div class="form-group">
                    <label for="client_name">Pickup City</label>
                    <input type="text" class="form-control" name="pickupcity"  id="pickupcity" placeholder="Enter Pickup City" required>
                  </div>
                  <div class="form-group">
                    <label for="client_name">Pincode</label>
                    <input type="text" class="form-control" name="pickup_pincode"  id="pincode" placeholder="Enter Pincode" required>
                  </div>
                  <div class="form-group">
                    <label for="client_name">Sender Contact No.</label>
                    <input type="text" class="form-control" name="sendercontactno"  id="sendercontactno" placeholder="Enter Sender Contact No" required>
                  </div>
                  </div>
                  </div>
                </div>
                 <div class="col-md-4">
                     <div class="card card-primary">
              <div class="card-header" style="background-color: #fff;color: black;">
                <h3 class="card-title" style="
    color: #d13d11;
    font-weight: 800;
">Consignee Details</h3>
              </div>   
              <div class="card-body">
                      <div class="form-group">
                    <label for="client_name">Receiver Name</label>
                    <input type="text" class="form-control" name="con_client_name"  id="con_client_name" placeholder="Enter Receiver name" required>
                  </div>
                  <div class="form-group">
                    <label for="pickupaddress">Receiver Address</label>
                    <input type="text" class="form-control" name="receiveraddress"  id="receiveraddress" placeholder="Enter Receiver Address" required>
                  </div>
                  <div class="form-group">
                    <label for="client_name">Receiver State</label>
                    <input type="text" class="form-control" name="reciverstate"  id="receiverstate" placeholder="Enter Receiver State" required>
                  </div>
                  <div class="form-group">
                    <label for="client_name">Receiver City</label>
                    <input type="text" class="form-control" name="receivercity"  id="receivercity" placeholder="Enter Receiver City" required>
                  </div>
                  <div class="form-group">
                    <label for="client_name">Pincode</label>
                    <input type="text" class="form-control" name="receiver_pincode"  id="pincode" placeholder="Enter Pincode" required>
                  </div>
                  <div class="form-group">
                    <label for="client_name">Receiver Contact No.</label>
                    <input type="text" class="form-control" name="receivercontactno"  id="receivercontactno" placeholder="Enter Receiver Contact No" required>
                  </div>
                  </div>
                  </div>
                </div>
                <!-- /.card-body -->
                </div>
                </div>
                <div class="card-footer">
                  <button type="submit" class="btn btn-primary">Submit</button>
                </div>
              </form>
              </div>
              <script>
                    function toggleDiv() {
                        var div = document.getElementById("ouickform");
                        if (div.style.display === "none" || div.style.display === "") {
                            div.style.display = "block";
                        } else {
                            div.style.display = "none";
                        }
                    }
                    
                    // Auto-calculate vol_weight from L*B*H in grams
                    function calculateVolWeight() {
                        var length = parseFloat(document.getElementById('length').value) || 0;
                        var breadth = parseFloat(document.getElementById('breadth').value) || 0;
                        var height = parseFloat(document.getElementById('height').value) || 0;
                        var pices = parseFloat(document.getElementById('pices').value) || 1;
                        
                        if (length > 0 && breadth > 0 && height > 0) {
                            // Volumetric weight formula: (L × B × H × pieces) / 5000 (in kg), then convert to grams
                            var volWeightKg = (length * breadth * height * pices) / 5000;
                            var volWeightGram = volWeightKg * 1000; // Convert to grams
                            document.getElementById('vol_weight').value = volWeightGram.toFixed(2);
                        } else {
                            document.getElementById('vol_weight').value = '';
                        }
                    }
                    
                    // Add event listeners for auto-calculation
                    document.addEventListener('DOMContentLoaded', function() {
                        var lengthInput = document.getElementById('length');
                        var breadthInput = document.getElementById('breadth');
                        var heightInput = document.getElementById('height');
                        var picesInput = document.getElementById('pices');
                        
                        if (lengthInput) lengthInput.addEventListener('input', calculateVolWeight);
                        if (breadthInput) breadthInput.addEventListener('input', calculateVolWeight);
                        if (heightInput) heightInput.addEventListener('input', calculateVolWeight);
                        if (picesInput) picesInput.addEventListener('input', calculateVolWeight);
                    });
                </script>
            </div>
            <!-- /.card -->
          </div>
          <div class="col-md-12">
            <!-- general form elements -->
            <div class="card card-primary">
              <div onclick="toggleDivbulk()" class="card-header">
                <h3 class="card-title">Bulk Booking & Update</h3>
              </div>
              <!-- /.card-header -->
                <div class="card-body" id="bulk" style="display: none;">
                <div class="row">
                    <div class="col-md-6">
                        <!-- general form elements -->
            <div class="card card-primary">
              <div class="card-header" style="background-color: #fff;color: black;">
                <h3 class="card-title">Bulk Booking</h3>
              </div>
              <!-- /.card-header -->
              <!-- form start -->
              <form method="POST" action="{{route('BulkBooking')}}" enctype="multipart/form-data" onsubmit="disableSubmitButton()">
                @csrf
                <div class="card-body">
                   
                   
                  <div class="form-group">
                        <label for="exampleInputFile"><a href="https://track.sbexpresscargo.com/storage/bulkbooking.xlsx" download="bulkbooking.xlsx">Download Format</a></label>
                        
                        <div class="input-group">
                        <div class="custom-file">
                        <input name="excel_file" type="file" class="custom-file-input" id="exampleInputFile">
                        <label class="custom-file-label" for="exampleInputFile">Choose file to Submit </label>
                        </div>
                        </div>
                        </div>
                </div>
                <!-- /.card-body -->

                <div class="card-footer">
                  <button type="submit" class="btn btn-primary" id="submit-btn">Booking</button>
                </div>
              </form>
            </div>
            <!-- /.card -->
                </div>
                    <div class="col-md-6">
                         <!-- general form elements -->
            <div class="card card-primary">
              <div class="card-header" style="background-color: #fff;color: black;">
                <h3 class="card-title">Bulk Update</h3>
              </div>
              <!-- /.card-header -->
              <!-- form start -->
              <form method="POST" action="{{route('BulkUpdate')}}" enctype="multipart/form-data">
                @csrf
                <div class="card-body">
               
                    <div class="form-group">
                        <label for="exampleInputFile"><a href="https://track.sbexpresscargo.com/storage/bulkupdate.xlsx" download="bulkupdate.xlsx">Download Format</a></label>
                        <div class="input-group">
                        <div class="custom-file">
                        <input name="excel_file" type="file" class="custom-file-input" id="exampleInputFile2">
                        <label class="custom-file-label" for="exampleInputFile2">Choose file to update</label>
                        </div>
                        </div>
                        </div>
                </div>
                <!-- /.card-body -->

                <div class="card-footer">
                  <button type="submit" class="btn btn-primary">Update</button>
                </div>
              </form>
            </div>
            <!-- /.card -->
                    </div>
                </div>
                </div>
                <script>
                    function toggleDivbulk() {
                        var div = document.getElementById("bulk");
                        if (div.style.display === "none" || div.style.display === "") {
                            div.style.display = "block";
                        } else {
                            div.style.display = "none";
                        }
                    }
                </script>
                <!-- /.card-body -->
            </div>
            <!-- /.card -->
          </div>
          <div class="col-12">
            <div class="card">
              <!-- /.card-header -->
              <div class="card-body">
                <table id="bookingtable" class="table ">
                  <thead>
                  <tr>
                    <th>#</th>
                    <th>Creation Date & Time</th>
                     <th>Booking Date & Time</th>
                    <th>AWB No.</th>
                    <th>Customer Name</th>
                    <th>Pickup Location</th>
       
                    <th>Delivery Location</th>
                    <th>Status</th>
                    <th>Action</th>
                  </tr>
                  </thead>
   
                  <tfoot>
                  <tr>
                    <th>#</th>
                     <th>Creation Date & Time</th>
                     <th>Booking Date & Time</th>
                    <th>AWB No.</th>
                    <th>Customer Name</th>
                    <th>Pickup Location</th>
        
                    <th>Delivery Location</th>
                    
                    <th>Status</th>
            
                    <th>Action</th>
                  </tr>
                  </tfoot>
                </table>
              </div>
              <!-- /.card-body -->
            </div>
            <!-- /.card -->

           
          
          </div>
          <!-- /.col -->
        </div>
        <!-- /.row -->
      </div>
      <!-- /.container-fluid -->
    <!-- /.content -->
  </div>
  
   <script>
$(document).ready(function () {
    // Destroy existing DataTable if it exists to prevent reinitialization error
    if ($.fn.DataTable.isDataTable('#bookingtable')) {
        $('#bookingtable').DataTable().destroy();
    }
    
    var table = $('#bookingtable').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": "{{ route('Booking.index') }}",
        "order": [[0, 'desc']],
        "columns": [
            { data: 'id', name: 'id' },
            { 
                data: 'created_at',
                name: 'created_at',
                render: function(data, type, row) {
                    if (type === 'display' || type === 'filter') {
                        return new Date(data).toLocaleString();
                    }
                    return data;
                }
            },
            { data: 'booking_date', name: 'booking_date' },
            { data: 'forwordingno', name: 'forwordingno' },
            { data: 'cust_name', name: 'cust_name' },
            { data: 'pickuplocation', name: 'pickuplocation' },
            { data: 'deliverylocation', name: 'deliverylocation' }, 
            { data: 'status', name: 'status' }, 
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ]
    });
});
</script>

@endsection