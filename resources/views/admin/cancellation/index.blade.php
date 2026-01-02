@extends('admin.layouts.tabelapp', ['activePage' => 'cancellation', 'titlePage' => __('Cancellation Requests')])

@section('content')
    <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Cancellation Requests</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
              <li class="breadcrumb-item active">Cancellation Requests</li>
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
              <div class="card-header">
                <h3 class="card-title">Manage Cancellation Requests</h3>
              </div>
              <!-- /.card-header -->
              <div class="card-body">
                <table id="example1" class="table table-bordered table-striped">
                  <thead>
                  <tr>
                    <th>#</th>
                    <th>Waybill</th>
                    <th>Booking ID</th>
                    <th>Client</th>
                    <th>Provider</th>
                    <th>Status</th>
                    <th>Remarks</th>
                    <th>Error Message</th>
                    <th>Created At</th>
                    <th>Updated At</th>
                    <th>Action</th>
                  </tr>
                  </thead>
                  <tbody>
                    @if (count($cancellationRequests) > 0)
                      @php ($i = 1)
                      @foreach ($cancellationRequests as $request)
                        <tr>
                          <td>{{ $i }}</td>
                          <td>{{ $request->waybill ?? 'N/A' }}</td>
                          <td>
                            @if($request->booking)
                              <a href="{{ route('Booking-showdetails', $request->booking_id) }}" target="_blank">
                                {{ $request->booking_id }}
                              </a>
                            @else
                              {{ $request->booking_id }}
                            @endif
                          </td>
                          <td>{{ $request->webhook->name ?? 'N/A' }}</td>
                          <td>{{ $request->provider ?? 'N/A' }}</td>
                          <td>
                            <span class="badge badge-{{ $request->status == 'cancelled' ? 'success' : ($request->status == 'declined' ? 'danger' : ($request->status == 'processing' ? 'warning' : ($request->status == 'mailed' ? 'info' : 'secondary'))) }}">
                              {{ ucfirst($request->status) }}
                            </span>
                          </td>
                          <td>{{ $request->remarks ? (strlen($request->remarks) > 50 ? substr($request->remarks, 0, 50) . '...' : $request->remarks) : 'N/A' }}</td>
                          <td>{{ $request->error_message ? (strlen($request->error_message) > 50 ? substr($request->error_message, 0, 50) . '...' : $request->error_message) : 'N/A' }}</td>
                          <td>{{ $request->created_at ? $request->created_at->format('Y-m-d H:i:s') : 'N/A' }}</td>
                          <td>{{ $request->updated_at ? $request->updated_at->format('Y-m-d H:i:s') : 'N/A' }}</td>
                          <td>
                            <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#updateStatusModal{{ $request->id }}">
                              <i class="fas fa-edit"></i> Update Status
                            </button>
                          </td>
                        </tr>

                        <!-- Update Status Modal -->
                        <div class="modal fade" id="updateStatusModal{{ $request->id }}" tabindex="-1" role="dialog" aria-labelledby="updateStatusModalLabel{{ $request->id }}" aria-hidden="true">
                          <div class="modal-dialog" role="document">
                            <div class="modal-content">
                              <div class="modal-header">
                                <h5 class="modal-title" id="updateStatusModalLabel{{ $request->id }}">Update Cancellation Request Status</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                  <span aria-hidden="true">&times;</span>
                                </button>
                              </div>
                              <form action="{{ route('cancellation-requests.update-status', $request->id) }}" method="POST">
                                @csrf
                                <div class="modal-body">
                                  <div class="form-group">
                                    <label for="status{{ $request->id }}">Status</label>
                                    <select class="form-control" id="status{{ $request->id }}" name="status" required>
                                      <option value="{{ \App\Models\CancellationRequest::STATUS_REQUESTED }}" {{ $request->status == \App\Models\CancellationRequest::STATUS_REQUESTED ? 'selected' : '' }}>
                                        Requested
                                      </option>
                                      <option value="{{ \App\Models\CancellationRequest::STATUS_PROCESSING }}" {{ $request->status == \App\Models\CancellationRequest::STATUS_PROCESSING ? 'selected' : '' }}>
                                        Processing
                                      </option>
                                      <option value="{{ \App\Models\CancellationRequest::STATUS_MAILED }}" {{ $request->status == \App\Models\CancellationRequest::STATUS_MAILED ? 'selected' : '' }}>
                                        Mailed
                                      </option>
                                      <option value="{{ \App\Models\CancellationRequest::STATUS_CANCELLED }}" {{ $request->status == \App\Models\CancellationRequest::STATUS_CANCELLED ? 'selected' : '' }}>
                                        Cancelled
                                      </option>
                                      <option value="{{ \App\Models\CancellationRequest::STATUS_DECLINED }}" {{ $request->status == \App\Models\CancellationRequest::STATUS_DECLINED ? 'selected' : '' }}>
                                        Declined
                                      </option>
                                    </select>
                                  </div>
                                  <div class="form-group">
                                    <label for="remarks{{ $request->id }}">Remarks</label>
                                    <textarea class="form-control" id="remarks{{ $request->id }}" name="remarks" rows="3" placeholder="Enter remarks (optional)">{{ $request->remarks }}</textarea>
                                  </div>
                                  <div class="form-group">
                                    <label for="error_message{{ $request->id }}">Error Message</label>
                                    <textarea class="form-control" id="error_message{{ $request->id }}" name="error_message" rows="2" placeholder="Enter error message if any (optional)">{{ $request->error_message }}</textarea>
                                  </div>
                                </div>
                                <div class="modal-footer">
                                  <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                  <button type="submit" class="btn btn-primary">Update Status</button>
                                </div>
                              </form>
                            </div>
                          </div>
                        </div>
                        @php ($i++)
                      @endforeach
                    @else
                      <tr>
                        <td colspan="11" class="text-center">No cancellation requests found.</td>
                      </tr>
                    @endif
                  </tbody>
                  <tfoot>
                  <tr>
                    <th>#</th>
                    <th>Waybill</th>
                    <th>Booking ID</th>
                    <th>Client</th>
                    <th>Provider</th>
                    <th>Status</th>
                    <th>Remarks</th>
                    <th>Error Message</th>
                    <th>Created At</th>
                    <th>Updated At</th>
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

