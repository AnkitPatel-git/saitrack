 @extends('admin.layouts.tabelapp', ['activePage' => 'dashboard', 'titlePage' => __('Dashboard')])

@section('content')
    <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Pincode </h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Pincode</li>
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
                <h3 class="card-title">Pincode</h3>
              </div>
              <!-- /.card-header -->
              <!-- form start -->
           
                    <div id="ouickform" style="display: none;">
              <form method="POST" action="{{route('pincode.store')}}">
                @csrf
                <div class="card-body">
                    <div class="row">
                    <div class="col-md-4">
                    <div class="form-group">
                    <label for="booking_office">Pincode</label>
                    <input type="text" class="form-control" name="pincode"  id="pincode" required>
                  </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group">
                    <label for="city">Area</label>
                    <input type="text" class="form-control" name="area"  id="area" required>
                  </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group">
                    <label for="district">District</label>
                    <input type="text" class="form-control" name="district"  id="district" required>
                  </div>
                  </div>
                  
                  <div class="col-md-4">
                    <div class="form-group">
                    <label for="booking_date">State</label>
                    <input type="text" class="form-control" name="state"  id="state" required>
                  </div>
                  </div>
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
                <h3 class="card-title">Bulk Pincode Upload </h3>
              </div>
              <!-- /.card-header -->
                <div class="card-body" id="bulk" style="display: none;">
                <div class="row">
                    <div class="col-md-12">
                        <!-- general form elements -->
            <div class="card card-primary">
              <div class="card-header" style="background-color: #fff;color: black;">
                <h3 class="card-title">Bulk Upload</h3>
              </div>
              <!-- /.card-header -->
              <!-- form start -->
              <form method="POST" action="{{route('BulkPincode')}}" enctype="multipart/form-data" onsubmit="disableSubmitButton()">
                @csrf
                <div class="card-body">
                  <div class="form-group">
                        <label for="exampleInputFile"><a href="https://track.sbexpresscargo.com/storage/BulkPincode.xlsx" download="BulkPincode.xlsx">Download Format</a></label>
                        
                        <div class="input-group">
                        <div class="custom-file">
                        <input name="excel_file" type="file" class="custom-file-input" id="exampleInputFile">
                        <label class="custom-file-label" for="exampleInputFile">Choose file to create </label>
                        </div>
                        </div>
                        </div>
                </div>
                <!-- /.card-body -->

                <div class="card-footer">
                  <button type="submit" class="btn btn-primary" id="submit-btn">Create</button>
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
                <table id="pincodetable" class="table ">
                  <thead>
                  <tr>
                    <th>#</th>
                    <th>Pincode</th>
                    <th>Area</th>
                    <th>District</th>
                    <th>State</th>
                    <th>Action</th>
                  </tr>
                  </thead>
            
                  <tfoot>
                  <tr>
                   <th>#</th>
                    <th>Pincode</th>
                    <th>Area</th>
                    <th>District</th>
                    <th>State</th>
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
        $('#pincodetable').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": "{{ route('pincode.index') }}",
        "order": [[0, 'desc']],
        "columns": [
            { data: 'id', name: 'id' },
            { data: 'pincode', name: 'pincode' },
            { data: 'area', name: 'area' },
            { data: 'district', name: 'district' },
            { data: 'state', name: 'state' },
            { data: 'action', name: 'action', orderable: false, searchable: false },
            
        ],
    
        });
    });
</script>
@endsection