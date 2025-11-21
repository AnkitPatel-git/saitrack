 @extends('admin.layouts.tabelapp', ['activePage' => 'dashboard', 'titlePage' => __('Dashboard')])

@section('content')
    <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Pan card </h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Pan card</li>
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
                <h3 class="card-title">Pan card</h3>
              </div>
              <!-- /.card-header -->
              <!-- form start -->
           
                    <!--<div id="ouickform" style="display: none;">-->
              <form method="POST" action="{{route('user-pancardcheck')}}" target="_blank">
                        @csrf 
           
                
                <div class="card-body">
                    <div class="row">
                    <div class="col-md-4">
                    <div class="form-group">
                    <label for="booking_office">Pan card</label>
                    <input type="text" class="form-control" name="Pancard" value=""  id="Pancard" required>
                  </div>
                  </div>
                  
                  </div>
                    </div>
 
          
                <div class="card-footer">
                  <button type="submit" class="btn btn-primary">Submit</button>
                </div>
              </form>
              </div>
              
            <!--</div>-->
            <!-- /.card -->
          </div>
              
          <!-- /.col -->
        </div>
        <!-- /.row -->
      </div>
      <!-- /.container-fluid -->
    <!-- /.content -->
  </div>
@endsection