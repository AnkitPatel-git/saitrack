 @extends('admin.layouts.app', ['activePage' => 'dashboard', 'titlePage' => __('Dashboard')])

@section('content')
 <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1> Booking</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item ">Booking</li>
          
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
              <div onclick="toggleDiv()" class="card-header"   href="{{route('Booking-showdetails',$data->id)}}">
                <h3 class="card-title">Booking Details</h3>
              </div>
              <!-- /.card-header -->
              <!-- form start -->
           
                    <div id="ouickform" style="display: none;">
              
                @csrf
                <div class="card-body">
                    <div class="row">
                    <div class="col-md-6">
                    <div class="form-group">
                    <label for="booking_office">Booking Office :  </label> 
                  </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                    <label for="booking_date">Booking Date&Time :</label>
                     
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
                  	 
                <!--<p><b>Customer Name: </b> {{ $data->cust_name }}  </p>-->
                <!--<p><b>AWB No:</b> {{ $data->forwordingno }}</p>-->
                <!--<p><b>Pickup Location : </b>{{ $data->pickuplocation }} </p>-->
                <!--<p><b>Delivery Location: </b> {{ $data->deliverylocation }} </p>-->
                <!--<p><b>Product Type: </b> {{ $data->product_type }} </p> -->
                <!--<p><b>Weight: </b> {{ $data->weight }} </p>-->
                <!--<p><b>Vol. Weight: </b> {{ $data->vol_weight }} </p>-->
                <!--<p><b>Chargable Weight: </b> {{ $data->charg_weight }} </p>-->
                	<table class="table table-borderless ">
			      	<tr>
			      		<th>Customer Name: </th>
			      		<td> {{ $data->cust_name }}</td>
			      	</tr>
			      	<tr>
			      		<th>AWB No:</th>
			      		<td> {{ $data->forwordingno }} </td>
			      	</tr>
			      	<tr>
			      		<th >Pickup Location : </th>
			      		<td>{{ $data->pickuplocation }}</td>
			      	</tr>
			      	
			      		<tr>
			      		<th >Delivery Location:</th>
			      		<td> {{ $data->deliverylocation }}</td>
			      	</tr>
			      	<tr>
			      		<th>Product Type:</th>
			      		<td> {{ $data->product_type }} </td>
			      	</tr>
			      	<tr>
			      		<th>Weight: </th>
			      		<td>{{$data->weight}}</td>
			      	</tr>
			      	<tr>
			      		<th>Vol. Weight:  </th>
			      		<td>{{ $data->vol_weight }}</td>
			      	</tr>
			      		<tr>
			      		<th>Chargable Weight:  </th>
			      		<td>{{ $data->charg_weight }}</td>
			      	</tr>
				     </table>
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
                  <!--<p>Client Name:</p>-->
                  <!--<p>Pickup Address:</p>-->
                  <!--<p>Pickup State:</p>-->
                  <!--<p>Pickup City:</p>-->
                  <!--<p>Pincode:</p>-->
                  <!--<p>Sender Contact No</p>-->
                  	<table class="table table-borderless ">
			      	<tr>
			      		<th>Client Name: </th>
			      		<td> {{ $data->client_name }}</td>
			      	</tr>
			      	<tr>
			      		<th>Pickup Address:</th>
			      		<td> {{ $data->pickupaddress }} </td>
			      	</tr>
			      	<tr>
			      		<th >Pickup State:</th>
			      		<td>{{ $data->pickup_name }}</td>
			      	</tr>
			      	
			      		<tr>
			      		<th >Pickup City:</th>
			      		<td> {{ $data->pickupcity }}</td>
			      	</tr>
			      	<tr>
			      		<th>Pincode:</th>
			      		<td> {{ $data->pickup_pincode }} </td>
			      	</tr>
			      	<tr>
			      		<th>Sender Contact No: </th>
			      		<td>{{$data->sendercontactno}}</td>
			      	</tr> 
				     </table>
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
                  <!--<p>Client Name:</p>-->
                  <!--<p>Receiver Address:</p>-->
                  <!--<p>Receiver State:</p>-->
                  <!--<p>Receiver City:</p>-->
                  <!--<p>Pincode:</p>-->
                  <!--<p>Reciver Contact No:</p> -->
                  	<table class="table table-borderless ">
			      	<tr>
			      		<th>Client Name </th>
			      		<td> {{ $data->con_client_name }}</td>
			      	</tr>
			      	<tr>
			      		<th>Receiver Address:</th>
			      		<td> {{ $data->receiveraddress }} </td>
			      	</tr>
			      	<tr>
			      		<th >Receiver State:</th>
			      		<td>{{ $data->receiverstate }}</td>
			      	</tr>
			      	
			      		<tr>
			      		<th >Receiver City:</th>
			      		<td> {{ $data->receivercity }}</td>
			      	</tr>
			      	<tr>
			      		<th>Pincode:</th>
			      		<td> {{ $data->receiver_pincode }} </td>
			      	</tr>
			      	<tr>
			      		<th>Reciver Contact No: </th>
			      		<td>{{$data->receivercontactno}}</td>
			      	</tr> 
				     </table>
                  </div>
                  </div>
                </div>
                <!-- /.card-body -->
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