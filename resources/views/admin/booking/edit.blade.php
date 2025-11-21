 @extends('admin.layouts.app', ['activePage' => 'dashboard', 'titlePage' => __('Dashboard')])

@section('content')
 <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Update Booking</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item ">Booking</li>
              <li class="breadcrumb-item active">Update</li>
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
              <div onclick="toggleDiv()" class="card-header" href="{{route('Booking-showdetails',$data->id)}}">
                <h3 class="card-title">Booking Details</h3>
              </div>
              <!-- /.card-header -->
              <!-- form start -->
           
                    <div id="ouickform" style="display: none;">
              
                @csrf
                <div class="card-body">
                     <form method="POST" action="{{ route('booking.updateall', ['id' => $data->id]) }}">
                        @csrf
                        @method('PATCH') 
                      <div class="row">
                    <div class="col-md-6">
                    <div class="form-group">
                    <label for="booking_office">Booking Office</label>
                    <input type="text" class="form-control" name="booking_office"  id="booking_office">
                  </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                    <label for="booking_date">Booking Date&Time</label>
                    <input type="datetime-local" class="form-control" name="booking_date"  id="booking_date" >
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
                    <input type="text" class="form-control" name="cust_name"  id="cust_name" value="{{ $data->cust_name }}" >
                  </div>
                  <div class="form-group">
                    <label for="forwordingno">AWB No.</label>
                    <input type="text" class="form-control" name="forwordingno"  id="forwordingno" value="{{ $data->forwordingno }}"  readonly>
                  </div>
                  <div class="form-group">
                    <label for="forwordingno">Refrence No.</label>
                    <input type="text" class="form-control" name="refrenceno"  id="refrenceno" value="{{ $data->refrenceno }} " >
                  </div>
                 
                 
                   <div class="row">
                    <div class="col-md-6">
                       <div class="form-group">
                    <label for="pickuplocation">Pickup Location</label>
                    <input type="text" class="form-control" name="pickuplocation"  id="pickuplocation" value="{{ $data->pickuplocation }}">
                  </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                    <label for="deliverylocation">Delivery Location</label>
                    <input type="text" class="form-control" name="deliverylocation"  id="deliverylocation" value=" {{ $data->deliverylocation }}" >
                  </div>
                    </div> 
                  </div>
                   <div class="row">
                    <div class="col-md-6">
                      <div class="form-group">
                    <label for="product_type">Product Type</label>
                    <input type="text" class="form-control" name="product_type"  id="product_type" value="{{ $data->product_type }}" >
                  </div>
                    </div>
                    <div class="col-md-6">
                       <div class="form-group">
                    <label for="deliverylocation">Content</label>
                    <input type="text" class="form-control" name="content"  id="content" value="{{ $data->content }}">
                  </div>
                    </div> 
                  </div>
                    <div class="row">
                    <div class="col-md-3">
                      <div class="form-group">
                    <label for="weight">Weight</label>
                    <input type="text" class="form-control" name="weight"  id="weight" value="{{ $data->weight }}" >
                  </div> 
                    </div>
                    <div class="col-md-4">
                      <div class="form-group">
                    <label for="vol_weight">(L*B*H) Weight</label>
                    <input type="text" class="form-control" name="vol_weight"  id="vol_weight" value="{{ $data->vol_weight }}" >
                  </div> 
                    </div>
                    <div class="col-md-5">
                      <div class="form-group">
                    <label for="charg_weight">Chargable Weight</label>
                    <input type="text" class="form-control" name="charg_weight"  id="charg_weight" value="{{ $data->charg_weight }}" >
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
                    <input type="text" class="form-control" name="client_name"  id="client_name" value="{{ $data->client_name }}" >
                  </div>
                  <div class="form-group">
                    <label for="pickupaddress">Pickup Address</label>
                    <input type="text" class="form-control" name="pickupaddress"  id="pickupaddress" value="{{ $data->pickupaddress }}">
                  </div>
                  <div class="form-group">
                    <label for="client_name">Pickup State</label>
                    <input type="text" class="form-control" name="pickup_name"  id="pickup_name" value="{{ $data->pickup_name }}">
                  </div>
                  <div class="form-group">
                    <label for="client_name">Pickup City</label>
                    <input type="text" class="form-control" name="pickupcity"  id="pickupcity" value="{{ $data->pickupcity }}">
                  </div>
                  <div class="form-group">
                    <label for="client_name">Pincode</label>
                    <input type="text" class="form-control" name="pickup_pincode"  id="pincode" value="{{ $data->pickup_pincode }}" >
                  </div>
                  <div class="form-group">
                    <label for="client_name">Sender Contact No.</label>
                    <input type="text" class="form-control" name="sendercontactno"  id="sendercontactno" value="{{$data->sendercontactno}}">
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
                    <label>Receiver Name</label>
                    <input type="text" class="form-control" name="con_client_name"  id="con_client_name" value="{{ $data->con_client_name }}">
                  </div>
                  <div class="form-group">
                    <label>Receiver Address</label>
                    <input type="text" class="form-control" name="receiveraddress"  id="receiveraddress" value="{{ $data->receiveraddress }}" >
                  </div>
                  <div class="form-group">
                    <label>Receiver State</label>
                    <input type="text" class="form-control" name="receiverstate"  id="receiverstate" value="{{ $data->receiverstate }}">
                  </div>
                  <div class="form-group">
                    <label>Receiver City</label>
                    <input type="text" class="form-control" name="receivercity"  id="receivercity" value="{{ $data->receivercity }}" >
                  </div>
                  <div class="form-group">
                    <label>Pincode</label>
                    <input type="text" class="form-control" name="receiver_pincode"  id="pincode" value="{{ $data->receiver_pincode }}" >
                  </div>
                  <div class="form-group">
                    <label>Receiver Contact No.</label>
                    <input type="text" class="form-control" name="receivercontactno"  id="receivercontactno" value="{{$data->receivercontactno}}">
                  </div>
                  </div>
                  </div> 
                   
                </div>
                  <button type="submit" class="btn btn-primary">{{ __('Update Booking') }}</button>
                    </form>
                 <!--/.card-body -->
                </div>
                </div>

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
              <div class="card-header">
                <h3 class="card-title">Update Form</h3>
              </div>
              <!-- /.card-header -->
              <!-- form start -->
              <form method="POST" action="{{route('Booking.update',$data->id)}}">
                @csrf
                @method('PUT')
                <div class="card-body">
                   <div class="form-group">
                    <label for="currentstatus">Current Place</label>
                    <input type="text" class="form-control"   id="currentstatus" name="currentstatus" placeholder="Enter Current Location of parcel">
                  </div>
                  <div class="form-group">
                    <label for="currentstatus">Details</label>
                    <input type="text" class="form-control"   id="currentstatus" name="remark" placeholder="Enter Any Remark">
                  </div>
                   <div class="form-group">
                    <label for="booking_date">Delivery Date & Time</label>
                    <input type="datetime-local" class="form-control" name="deliverydate"  id="booking_date" required>
                  </div>
                   <div class="form-group">
                    <label for="booking_date">Expected Delivery Date & Time</label>
                    <input type="datetime-local" class="form-control" name="expecteddeliverydate"  id="delivery_date">
                  </div>
                  <div class="form-group">
                    <label for="status">Status</label>
                    <select class="custom-select rounded-0" id="status" name="status"  required>
                    <option value="Booked">Booked</option>
                    <option value="Shipped">Shipped</option>
                    <option value="In-transit">In-transit</option>
                    <option value="Out For Delivery">Out For Delivery</option>
                    <option value="Incomplete/incorrect address">Consignee's Address Incomplete/Incorrect</option>
                     <option value="consignee shifted ">Consignee Shifted From the given address</option> 
                     <option value="customer refused to accept ">Customer refused to accept</option> 
                     <option value="ndr">NDR</option>
                    <option value="Delivered">Shipment Delivered</option>
                    <option value="RTO">RTO</option>
                  </select>
                  </div>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.2.3/jquery.min.js"></script>
<script type="text/javascript">
$('#status option[value= {{$data->status}} ]').attr("selected","selected");</script>
                </div>
                <!-- /.card-body -->
                <div class="card-footer">
                  <button type="submit" class="btn btn-primary">Submit</button>
                </div>
              </form>
            </div>
            <!-- /.card -->        
          </div>
          <div class="col-md-12">
            <!-- general form elements -->
            <div class="card card-primary">
              <div class="card-header">
                <h3 class="card-title">Booking Log</h3>
              </div>
              <!-- /.card-header -->
              <div class="card-body">
                <table class="table table-bordered table-striped">
                  <thead>
                  <tr> 
                    <th>#</th>
                    <th>Location</th>
                    <th>Remark</th>
                    <th>Status</th>
                    <th>Date</th>
                  </tr>
                  </thead>
                  <tbody>
                    @if (count($datalogs) > 0)
                @php ($i = 1)
                @foreach ($datalogs as $datalog)
                  <tr>
                    <td>{{ $i }}</td>
                    <td>{{ $datalog->currentstatus }}</td>
                    <td>{{ $datalog->remark }}</td>
                    <td>{{ $datalog->status }}</td>
                    <td>{{ $datalog->created_at }}</td>
                  </tr>
            @php ($i++)   
            @endforeach
            @endif
                  </tbody>
                </table>
              </div>
            </div>
            <!-- /.card -->        
          </div>
        </div>
        <!-- /.row -->
      </div>
      <!-- /.container-fluid -->
    </section>
    <!-- /.content -->
  </div>
@endsection