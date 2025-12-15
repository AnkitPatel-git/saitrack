@extends('admin.layouts.tabelapp', ['activePage' => 'dashboard', 'titlePage' => __('Dashboard')])

@section('content')
    <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Partner Management</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Partner Management</li>
            </ol>
          </div>
        </div>
      </div><!-- /.container-fluid -->
    </section>

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-12">
            <div class="card">
              <!-- /.card-header -->
              <div class="card-body">
                <table id="example1" class="table table-bordered table-striped">
                  <thead>
                  <tr>
                    <th>#</th>
                    <th>Organization Name</th>
                    <th>Contact Email</th>
                    <th>Service Provider</th>
                    <th>Test Account</th>
                    <th>Status</th>
                    <th>Created Date</th>
                    <th>Action</th>
                  </tr>
                  </thead>  
                  @if (count($datas) > 0)
                    @php ($i = 1)
                    @foreach ($datas as $data)
                      <tr>
                        <td>{{ $i }}</td>
                        <td>{{ $data->organization_name }}</td>
                        <td>{{ $data->contact_email }}</td>
                        <td>{{ $data->service_provider ?? 'N/A' }}</td>
                        <td>
                          <?php if($data->test == '1' || $data->test == 1){ ?>
                            <span class="badge badge-warning">Test</span>
                          <?php }else{ ?> 
                            <span class="badge badge-info">Production</span>
                          <?php } ?>
                        </td>
                        <td>
                          <?php if($data->is_active == '1'){ ?>
                            <span class="badge badge-success">Active</span>
                          <?php }else{ ?> 
                            <span class="badge badge-danger">Inactive</span>
                          <?php } ?>
                        </td>
                        <td>{{ $data->created_at }}</td> 
                        <td>
                          <a href="{{route('partner.show',$data->id)}}" class="btn btn-primary btn-sm">
                            <i class="fas fa-edit"></i> Edit Service Provider
                          </a>
                        </td>
                      </tr>
                      @php ($i++)   
                    @endforeach
                  @endif 
                  <tfoot>
                  <tr>
                    <th>#</th>
                    <th>Organization Name</th>
                    <th>Contact Email</th>
                    <th>Service Provider</th>
                    <th>Test Account</th>
                    <th>Status</th>
                    <th>Created Date</th>
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
    </section>
    <!-- /.content -->
  </div>
@endsection

