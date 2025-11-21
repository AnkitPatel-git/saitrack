@extends('admin.layouts.tabelapp', ['activePage' => 'client_api_logs', 'titlePage' => __('Waaree API Logs')])

@section('content')
<div class="content-wrapper">
  <!-- Content Header (Page header) -->
  <section class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1>Waaree API Logs</h1>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="#">Home</a></li>
            <li class="breadcrumb-item active">API Logs</li>
          </ol>
        </div>
      </div>
    </div>
  </section>

  <!-- Main content -->
  <section class="content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-body">
              <table id="logTable" class="table table-bordered table-hover">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Order Code</th>
                    <th>Channel</th>
                    <th>Mobile</th>
                    <th>Status</th>
                    <th>Display Order Time</th>
                    <th>Waaree Created</th>
                    <th>Sb Created</th>
                  </tr>
                </thead>
                <tfoot>
                  <tr>
                    <th>#</th>
                    <th>Order Code</th>
                    <th>Channel</th>
                    <th>Mobile</th>
                    <th>Status</th>
                    <th>Display Order Time</th>
                    <th>Waaree Created</th>
                    <th>Sb Created</th>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
</div>

<script>
  $(document).ready(function () {
    $('#logTable').DataTable({
      processing: true,
      serverSide: true,
      ajax: "{{ route('client.api.logs.ajax') }}",
      columns: [
        { data: 'id', name: 'id' },
        { data: 'displayOrderCode', name: 'displayOrderCode' },
        { data: 'channel', name: 'channel' },
        { data: 'notificationMobile', name: 'notificationMobile' },
        { data: 'status', name: 'status' },
        { 
          data: 'displayOrderDateTime', 
          name: 'displayOrderDateTime',
          render: function(data) {
            return new Date(data).toLocaleString();
          }
        },
        { 
          data: 'created', 
          name: 'created waaree',
          render: function(data) {
            return new Date(data).toLocaleString();
          }
        },
        { 
          data: 'created_at', 
          name: 'updated',
          render: function(data) {
            return new Date(data).toLocaleString();
          }
        },
      ]
    });
  });
</script>
@endsection
