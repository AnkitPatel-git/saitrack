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
                          <input type="text" class="form-control" name="service_provider" id="service_provider" value="{{ $data->service_provider }}" placeholder="e.g., bluedart, delhivery">
                          <small class="form-text text-muted">Enter the service provider name (e.g., bluedart, delhivery)</small>
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

