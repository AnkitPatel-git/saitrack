@extends('admin.layouts.tabelapp', ['activePage' => 'dashboard', 'titlePage' => __('Dashboard')])

@section('content')
    <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Mis Report </h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Mis Report</li>
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
            <!-- Filter Form Section -->
            <div class="card card-primary">
              <div class="card-header">
                <h3 class="card-title">MIS Report Filters</h3>
              </div>
              <form method="GET" action="{{ route('mis-report-download') }}" id="misReportForm">
                <div class="card-body">
                  <div class="row">
                    <div class="col-md-3">
                      <div class="form-group">
                        <label for="date_from">Date From</label>
                        <input type="date" class="form-control" id="date_from" name="date_from" value="{{ request('date_from') }}">
                      </div>
                    </div>
                    <div class="col-md-3">
                      <div class="form-group">
                        <label for="date_to">Date To</label>
                        <input type="date" class="form-control" id="date_to" name="date_to" value="{{ request('date_to') }}">
                      </div>
                    </div>
                    <div class="col-md-3">
                      <div class="form-group">
                        <label for="client_name">Client Name</label>
                        <input type="text" class="form-control" id="client_name" name="client_name" value="{{ request('client_name') }}" placeholder="Search by client name">
                      </div>
                    </div>
                    <div class="col-md-3">
                      <div class="form-group">
                        <label for="status">Status</label>
                        <select class="form-control" id="status" name="status">
                          <option value="">All Status</option>
                          <option value="Booked" {{ request('status') == 'Booked' ? 'selected' : '' }}>Booked</option>
                          <option value="Shipped" {{ request('status') == 'Shipped' ? 'selected' : '' }}>Shipped</option>
                          <option value="In Transit" {{ request('status') == 'In Transit' ? 'selected' : '' }}>In Transit</option>
                          <option value="Intransit" {{ request('status') == 'Intransit' ? 'selected' : '' }}>Intransit</option>
                          <option value="Out For Deliver" {{ request('status') == 'Out For Deliver' ? 'selected' : '' }}>Out For Deliver</option>
                          <option value="Delivered" {{ request('status') == 'Delivered' ? 'selected' : '' }}>Delivered</option>
                          <option value="RTO" {{ request('status') == 'RTO' ? 'selected' : '' }}>RTO</option>
                        </select>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="card-footer">
                  <button type="submit" class="btn btn-primary">Download MIS Report</button>
                  <a href="{{ route('misreport') }}" class="btn btn-secondary">Reset</a>
                </div>
              </form>
            </div>
            <!-- /.card -->
          </div>
        </div>
        
        <div class="row">
          <div class="col-md-12">
            <div class="card">
              <!-- /.card-header -->
              <div class="card-body">
               <table id="example1" class="table table-bordered table-striped">
  <thead>
    <tr>
      <th>#</th>
      <th>Waybill No</th>
       <th>Forwording No</th>
      <th>Origin Area</th>
      <th>Destination Area</th>
      <th>Product Code</th>
      <th>Pu Date</th>
      <th>Customer Code</th>
      <th>SHIPPER</th>
      <th>CONSIGNEE</th>
      <th>PIECES</th>
      <th>Bill Weight</th>
      <th>Actual Weight</th>
      <th>Qty</th>
      <th>Length</th>
      <th>Breadth</th>
      <th>Height</th>
      <th>Dimensional Weight</th>
      <th>Chargeable Weight</th>
    </tr>
  </thead>
  <tbody>
    @foreach($bookings as $index => $booking)
      <tr>
        <td>{{ $index + 1 }}</td>
        <td>{{ $booking['Waybill_No'] }}</td>
         <td>{{ $booking['Forwording_No'] }}</td>
        <td>{{ $booking['Origin_Area'] }}</td>
        <td>{{ $booking['Destination_Area'] }}</td>
        <td>{{ $booking['Product_Code'] }}</td>
        <td>{{ $booking['Pu_Date'] }}</td>
        <td>{{ $booking['Customer_Code'] }}</td>
        <td>{{ $booking['SHIPPER'] }}</td>
        <td>{{ $booking['CONSIGNEE'] }}</td>
        <td>{{ $booking['PIECES'] }}</td>
        <td>{{ $booking['Bill_Weight'] }}</td>
        <td>{{ $booking['Actual_Weight'] }}</td>
        <td>{{ $booking['Qty'] }}</td>
        <td>{{ $booking['Length'] }}</td>
        <td>{{ $booking['Breadth'] }}</td>
        <td>{{ $booking['Height'] }}</td>
        <td>{{ $booking['Dimensional_Weight'] }}</td>
        <td>{{ $booking['Chargeable_Weight'] }}</td>
      </tr>
    @endforeach
  </tbody>
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
    </section>
    <!-- /.content -->
  </div>
@endsection