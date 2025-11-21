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
              <li class="breadcrumb-item ">Booking / Upload POD</li>
          
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
              <div class="card-header">
                <h3 class="card-title">Upload POD</h3>
              </div>
              <!-- /.card-header -->
              <div class="card-body">
                  <div class="card card-primary">
              <div class="card-header" style="background-color: #fff;color: black;">
                <h3 class="card-title">Bulk Booking</h3>
              </div>
              <!-- /.card-header -->
              <!-- form start -->
              <form method="POST" action="{{route('submit-pod',$data->id)}}" enctype="multipart/form-data" onsubmit="disableSubmitButton()">
                @csrf
                <div class="card-body">
                   
                   
                  <div class="form-group">
                        <label for="exampleInputFile">Select IMG</label>
                        
                        <div class="input-group">
                        <div class="custom-file">
                        <input name="img_file" type="file" class="custom-file-input" id="exampleInputFile" required>
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