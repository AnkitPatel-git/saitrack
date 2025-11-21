 @extends('admin.layouts.app', ['activePage' => 'dashboard', 'titlePage' => __('Dashboard')])

@section('content')
 <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Update User Details</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item ">User Management</li>
              <li class="breadcrumb-item active">Update</li>
            </ol>
          </div>
        </div>
      </div><!-- /.container-fluid -->
    </section>

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
           <div class="card">
           <form method="POST" action="{{ route('user.update', ['user' => $datas->id]) }}">
            @csrf
            @method('PUT')
                <div class="card-body">
                    <div class="row">
                    <div class="col-md-6">
                    <div class="form-group">
                    <label for="booking_office">Employee Name</label>
                    <input type="text" class="form-control" name="name"  id="name" value="{{ $datas->name }}" >
                  </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                    <label for="booking_date">Email Address</label>
                    <input type="text" class="form-control" name="email"  id="email" value="{{ $datas->email }}" >
                  </div>
                  </div>
                     <div class="col-md-6">
                        <div class="form-group">
                            <label for="mobile">Mobile</label>
                            <input type="text" 
                                   class="form-control" 
                                   name="mobile" 
                                   id="mobile" 
                                   pattern="^[0-9]{10}$" 
                                   title="Please enter a valid 10-digit mobile number" 
                                   value="{{ $datas->mobile }}" 
                                   required>
                        </div>
                    </div>
                    </div>
                </div>
                <div class="card-footer">
                  <button type="submit" class="btn btn-primary">Update</button>
                </div>
              </form>
      </div>
      </div>
      <!-- /.container-fluid -->
    </section>
    <!-- /.content -->
  </div>
@endsection