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
                    <div class="col-md-4">
                      <div class="form-group">
                    <label for="vol_weight">(L*B*H) Weight</label>
                    <input type="text" class="form-control" name="vol_weight"  id="vol_weight" placeholder="Enter Vol weight" >
                  </div> 
                    </div>
                    <div class="col-md-5">
                      <div class="form-group">
                    <label for="charg_weight">Chargable Weight</label>
                    <input type="text" class="form-control" name="charg_weight"  id="charg_weight" placeholder="Enter Chargable Weight" >
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
        $('#bookingtable').DataTable({
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
                        // Convert 'created_at' to local timestamp format
                        if (type === 'display' || type === 'filter') {
                            return new Date(data).toLocaleString(); // Adjust the date formatting as needed
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
            
        ],
    
        });
    });
</script>
<script>
$(document).ready(function () {
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
            { 
                data: 'action', 
                name: 'action', 
                orderable: false, 
                searchable: false,
                render: function(data, type, row) {
                    return `
                        <button class="btn btn-sm btn-primary bluedart-btn" data-id="${row.id}">
                            BLUEDART
                        </button>
                    `;
                }
            },
        ]
    });

    // Handle BLUEDART button click
    $(document).on('click', '.bluedart-btn', function () {
        let bookingId = $(this).data('id');

        // Example payload (replace with bookingId-specific data if needed)
        let payload = {
             "Request": {
        "Consignee": {
            "AvailableDays": "Mon-Fri",
            "AvailableTiming": "10:00-18:00",
            "ConsigneeAddress1": "B-45, Rajouri Garden",
            "ConsigneeAddress2": "Near Metro Station",
            "ConsigneeAddress3": "West Delhi",
            "ConsigneeAddressType": "Residential",
            "ConsigneeAddressinfo": "",
            "ConsigneeAttention": "Mr. Patel",
            "ConsigneeEmailID": "ankit.patel@example.com",
            "ConsigneeFullAddress": "B-45, Rajouri Garden, Near Metro Station, West Delhi",
            "ConsigneeGSTNumber": "07ABCDE1234F1Z5",
            "ConsigneeLatitude": "28.643",
            "ConsigneeLongitude": "77.116",
            "ConsigneeMaskedContactNumber": "",
            "ConsigneeMobile": "8085305623",
            "ConsigneeName": "Ankit Patel",
            "ConsigneePincode": "110027",
            "ConsigneeTelephone": "011-41000000"
        },
        "Returnadds": {
            "ManifestNumber": "",
            "ReturnAddress1": "Warehouse Block A",
            "ReturnAddress2": "Andheri East",
            "ReturnAddress3": "Mumbai",
            "ReturnAddressinfo": "",
            "ReturnContact": "Returns Dept",
            "ReturnEmailID": "returns@example.com",
            "ReturnLatitude": "19.117",
            "ReturnLongitude": "72.865",
            "ReturnMaskedContactNumber": "",
            "ReturnMobile": "9995554447",
            "ReturnPincode": "400057",
            "ReturnTelephone": "022-66778899"
        },
        "Services": {
            "AWBNo": "",
            "ActualWeight": "0.50",
            "CollectableAmount": 0,
            "Commodity": {
                "CommodityDetail1": "Electronics",
                "CommodityDetail2": "Mobile",
                "CommodityDetail3": "Accessories"
            },
            "CreditReferenceNo": "ORDER123457",
            "CreditReferenceNo2": "INV7890",
            "CreditReferenceNo3": "",
            "DeclaredValue": 100,
            "DeliveryTimeSlot": "",
            "Dimensions": [
                {
                    "Breadth": 10,
                    "Count": 1,
                    "Height": 5,
                    "Length": 20
                }
            ],
            "FavouringName": "Ankit Patel",
            "IsDedicatedDeliveryNetwork": false,
            "IsDutyTaxPaidByShipper": false,
            "IsForcePickup": false,
            "IsPartialPickup": false,
            "IsReversePickup": true,
            "ItemCount": 1,
            "Officecutofftime": "1700",
            "PDFOutputNotRequired": true,
            "PackType": "BOX",
            "ParcelShopCode": "",
            "PayableAt": "",
            "PickupDate": "/Date(1722825600000)/", // 2025-08-06 00:00:00 GMT
            "PickupMode": "Regular",
            "PickupTime": "1600",
            "PickupType": "Reverse",
            "PieceCount": "1",
            "PreferredPickupTimeSlot": "1400-1600",
            "ProductCode": "A",
            "ProductFeature": "",
            "ProductType": 1,
            "RegisterPickup": true,
            "SpecialInstruction": "Handle with care. Call before delivery.",
            "SubProductCode": "P",
            "TotalCashPaytoCustomer": 0,
            "itemdtl": [
                {
                    "CGSTAmount": 9,
                    "HSCode": "85171200",
                    "IGSTAmount": 0,
                    "Instruction": "Fragile",
                    "InvoiceDate": "/Date(1722640000000)/", // 2025-08-04
                    "InvoiceNumber": "INV7890",
                    "ItemID": "ITEM123",
                    "ItemName": "Smartphone",
                    "ItemValue": 100,
                    "Itemquantity": 1,
                    "PlaceofSupply": "Delhi",
                    "ProductDesc1": "Samsung Galaxy A15",
                    "ProductDesc2": "Mobile Phone",
                    "ReturnReason": "Customer Return",
                    "SGSTAmount": 9,
                    "SKUNumber": "SKU12345",
                    "SellerGSTNNumber": "27ABCDE1234F1Z5",
                    "SellerName": "Tech Electronics Pvt Ltd",
                    "SubProduct1": "Mobiles",
                    "SubProduct2": "Electronics",
                    "TaxableAmount": 100,
                    "TotalValue": 118,
                    "cessAmount": "0.0",
                    "countryOfOrigin": "IN",
                    "docType": "TaxInvoice",
                    "subSupplyType": 1,
                    "supplyType": "Normal"
                }
            ],
            "noOfDCGiven": 0
        },
        "Shipper": {
            "CustomerAddress1": "A-23, Industrial Area",
            "CustomerAddress2": "Sector 34",
            "CustomerAddress3": "Gurgaon",
            "CustomerAddressinfo": "",
            "CustomerBusinessPartyTypeCode": "B2C",
            "CustomerCode": "480056",
            "CustomerEmailID": "support@saitrack.com",
            "CustomerGSTNumber": "06ABCDE1234F1Z1",
            "CustomerLatitude": "28.457",
            "CustomerLongitude": "77.026",
            "CustomerMaskedContactNumber": "",
            "CustomerMobile": "9996665554",
            "CustomerName": "Saitrack Solution",
            "CustomerPincode": "401208",
            "CustomerTelephone": "0124-4567890",
            "IsToPayCustomer": true,
            "OriginArea": "BOM",
            "Sender": "Warehouse Dispatch",
            "VendorCode": ""
        }
    },
            "Profile": {
                "LoginID": "BOM77977",
                "LicenceKey": "l8hgvmorurqgu8nss8kikukxwovloppp",
                "Api_type": "S"
            }
        };

        $.ajax({
            url: "https://apigateway-sandbox.bluedart.com/in/transportation/waybill/v1/GenerateWayBill",
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "JWTToken": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOiJzdWJqZWN0LXN1YmplY3QiLCJhdWQiOlsiYXVkaWVuY2UxIiwiYXVkaWVuY2UyIl0sImlzcyI6InVybjpcL1wvYXBpZ2VlLWVkZ2UtSldULXBvbGljeS10ZXN0IiwiZXhwIjoxNzU2MjcwMjIwLCJpYXQiOjE3NTYxODM4MjAsImp0aSI6IjNiNGU0NTQ3LTVhMjQtNDUyYy1iNjZiLTNmMDUxMzQ3NWVhNSJ9.2tZlyJ6rwH3uCHWbofoFrpOH8QHiW_8HQgzIrWGjEng" // 🔑 Your token here
            },
            data: JSON.stringify(payload),
            success: function(response) {
                alert("Waybill Generated Successfully: " + JSON.stringify(response));
            },
            error: function(xhr) {
                alert("Error: " + xhr.responseText);
            }
        });
    });
});
</script>

@endsection