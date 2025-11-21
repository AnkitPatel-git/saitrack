 @extends('admin.layouts.tabelapp', ['activePage' => 'dashboard', 'titlePage' => __('Dashboard')])

@section('content')
    <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>User Creation </h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">User Creation</li>
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
                <h3 class="card-title">User Creation</h3>
              </div>
              <!-- /.card-header -->
              <!-- form start -->
           
                    <div id="ouickform" style="display: none;">
              <form method="POST" action="{{route('user.store')}}">
                @csrf
                <div class="card-body">
                    <div class="row">
                    <div class="col-md-4">
                    <div class="form-group">
                    <label for="booking_office">Employee Name</label>
                    <input type="text" class="form-control" name="name"  id="name" required>
                  </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group">
                    <label for="booking_date">Email Address</label>
                    <input type="text" class="form-control" name="email"  id="email" required>
                  </div>
                  </div>
                   <div class="col-md-4">
                        <div class="form-group">
                            <label for="mobile">Mobile</label>
                            <input type="text" 
                                   class="form-control" 
                                   name="mobile" 
                                   id="mobile" 
                                   pattern="^[0-9]{10}$" 
                                   title="Please enter a valid 10-digit mobile number" 
                                   required>
                        </div>
                    </div>

                  <div class="col-md-4">
                    <div class="form-group">
                    <label for="booking_date">Password</label>
                    <input type="password" class="form-control" name="password"  id="password" required>
                  </div>
                  </div>
                  <!-- <div class="col-md-4">-->
                  <!--  <div class="form-group">-->
                  <!--  <label for="booking_date">Email Address</label>-->
                  <!--  <input type="text" class="form-control" name="city"  id="city" required>-->
                  <!--</div>-->
                  <!--</div>-->
                  <!-- <div class="col-md-4">-->
                  <!--  <div class="form-group">-->
                  <!--  <label for="booking_date">Email Address</label>-->
                  <!--  <input type="text" class="form-control" name="city"  id="city" required>-->
                  <!--</div>-->
                  <!--</div>-->
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
          <div class="col-12">
            <div class="card">
              <!-- /.card-header -->
              <div class="card-body">
                <table id="example1" class="table table-bordered table-striped">
                  <thead>
                  <tr>
                    <th>#</th>
                    <th>Employee Name</th>
                    <th>Email Address</th> 
                     <th>Created Date</th>
                    <th>Status</th>
                    <th>Action</th>
                  </tr>
                  </thead>  
                       @if (count($datas) > 0)
            @php ($i = 1)
            @foreach ($datas as $data)
                  <tr>
                    <td>{{ $i }}</td>
                    <td>{{ $data->name }}</td>
                    <td>{{ $data->email }}</td>
                    <td>{{ $data->created_at }}</td> 
                    <td> <?php if($data->status == '1'){ ?>
                                  <a href="{{ route('user-statusupdate',$data->id) }}" class="btn btn-success">Active</a
                                <?php }else{ ?> 
                                  <a href="{{ route('user-statusupdate',$data->id) }}" class="btn btn-danger">Inactive</a>
                                <?php } ?></td>
                    <td><a href="{{route('user.show',$data->id)}}"><i class="fas fa-edit"></i>Edit</a>
                    </td>
                  </tr>
            @php ($i++)   
            @endforeach
            @endif 
                  <tfoot>
                  <tr>
                   <th>#</th>
                    <th>Employee Name</th>
                    <th>Email Address</th>
                    <th>Created Date</th>
                    <th>Status</th>
                    <th>Action</th>
                  </tr>
                  </tfoot>
                </table>
              </div>
              <!-- /.card-body -->
            </div>
            <!-- /.card
          </div>
          <!-- /.col -->
        </div>
        <!-- /.row -->
      </div>
      <!-- /.container-fluid -->
    <!-- /.content -->
  </div>
@endsection