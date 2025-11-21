 @extends('admin.layouts.tabelapp', ['activePage' => 'dashboard', 'titlePage' => __('Dashboard')])

@section('content')
    <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Multiple Sticker Printing </h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Multiple Sticker Printing</li>
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
                <h3 class="card-title">Multiple Sticker Printing</h3>
              </div>
              <!-- /.card-header -->
              <!-- form start -->
                    <div>
             
                @csrf
                <div class="card-body">
                    <div class="row">
                 <div class="col-md-12">
                    <form method="post" action="{{ route('BulkStickerGen') }}" enctype="multipart/form-data" target="_blank">
                        @csrf
                        <div class="form-group">
                            <label for="forwordingno">AWB No</label>
                            <textarea class="form-control" id="forwordingno" name="forwordingno" rows="4" cols="50"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="exampleInputFile">
                                <a href="https://track.sbexpresscargo.com/storage/BulkInvoice.xlsx" download="BulkInvoice.xlsx">Download Format</a>
                            </label>
                            <div class="input-group">
                                <div class="custom-file">
                                    <input name="excel_file" type="file" class="custom-file-input" id="exampleInputFile">
                                    <label class="custom-file-label" for="exampleInputFile">Choose file to create</label>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">Submit</button>
                        </div>
                    </form>
</div>
                    

                    </div>
                </div>
              
              </div>
             
            </div>
            <!-- /.card -->
          </div>
      
        </div>
        <!-- /.row -->
      </div>
      <!-- /.container-fluid -->
    <!-- /.content -->
  </div>
@endsection