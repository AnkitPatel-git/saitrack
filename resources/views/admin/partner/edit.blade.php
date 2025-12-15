@extends('admin.layouts.app', ['activePage' => 'dashboard', 'titlePage' => __('Dashboard')])

@section('content')
 <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Update Service Provider</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item">Partner Management</li>
              <li class="breadcrumb-item active">Update Service Provider</li>
            </ol>
          </div>
        </div>
      </div><!-- /.container-fluid -->
    </section>

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
           <div class="card">
           <form method="POST" action="{{ route('partner.update', ['partner' => $data->id]) }}">
            @csrf
            @method('PUT')
                <div class="card-body">
                    <div class="row">
                      <div class="col-md-6">
                        <div class="form-group">
                          <label for="organization_name">Organization Name</label>
                          <input type="text" class="form-control" name="organization_name" id="organization_name" value="{{ $data->organization_name }}" readonly>
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="form-group">
                          <label for="contact_email">Contact Email</label>
                          <input type="text" class="form-control" name="contact_email" id="contact_email" value="{{ $data->contact_email }}" readonly>
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="form-group">
                          <label for="service_provider">Service Provider <span class="text-danger">*</span></label>
                          <select class="form-control" name="service_provider" id="service_provider" required>
                            <option value="">Select Service Provider</option>
                            <option value="sb" {{ $data->service_provider == 'sb' ? 'selected' : '' }}>SB</option>
                            <option value="bluedart" {{ $data->service_provider == 'bluedart' ? 'selected' : '' }}>BlueDart</option>
                            <option value="delhivery" {{ $data->service_provider == 'delhivery' ? 'selected' : '' }}>Delhivery</option>
                          </select>
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="form-group">
                          <label for="test">Test Account</label>
                          <input type="text" class="form-control" name="test" id="test" value="{{ ($data->test == '1' || $data->test == 1) ? 'Test' : 'Production' }}" readonly>
                          <small class="form-text text-muted">1 = Test Account, 0 = Production Account</small>
                        </div>
                      </div>
                    </div>
                </div>
                <div class="card-footer">
                  <button type="submit" class="btn btn-primary">Update Service Provider</button>
                  <a href="{{ route('partner.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
              </form>
      </div>
      </div>
      <!-- /.container-fluid -->
    </section>
    <!-- /.content -->
  </div>
@endsection

