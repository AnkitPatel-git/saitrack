<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ config('app.name') }}</title>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="{{ asset('asset') }}/plugins/fontawesome-free/css/all.min.css">
    <!-- Ionicons -->
  <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
  <!-- DataTables -->
  <link rel="stylesheet" href="{{ asset('asset') }}/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="{{ asset('asset') }}/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
  <link rel="stylesheet" href="{{ asset('asset') }}/plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="{{ asset('asset') }}/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="{{ asset('asset') }}/plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css">
  <link rel="stylesheet" href="{{ asset('asset') }}/plugins/toastr/toastr.min.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
   <script src="https://cdnjs.cloudflare.com/ajax/libs/jQuery.print/1.3.3/jQuery.print.min.js"></script>
  <style>
.switch {
  position: relative;
  display: inline-block;
  width: 60px;
  height: 34px;
}

.switch input { 
  opacity: 0;
  width: 0;
  height: 0;
}

.slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: #e74c3c;
  -webkit-transition: .4s;
  transition: .4s;
}

.slider:before {
  position: absolute;
  content: "";
  height: 26px;
  width: 26px;
  left: 4px;
  bottom: 4px;
  background-color: white;
  -webkit-transition: .4s;
  transition: .4s;
}

input:checked + .slider {
  background-color: #28a745;
}

input:focus + .slider {
  box-shadow: 0 0 1px #2196F3;
}

input:checked + .slider:before {
  -webkit-transform: translateX(26px);
  -ms-transform: translateX(26px);
  transform: translateX(26px);
}

/* Rounded sliders */
.slider.round {
  border-radius: 34px;
}

.slider.round:before {
  border-radius: 50%;
}
</style>
</head>
<body class="hold-transition sidebar-mini sidebar-collapse layout-fixed layout-navbar-fixed layout-footer-fixed">
<div class="wrapper">

  <!-- Preloader -->
  <div class="preloader flex-column justify-content-center align-items-center">
    <img class="animation__shake" src="{{ asset('image') }}/logo.png" alt="SB Logo" height="100" width="500">
  </div>

  <!-- Navbar -->
  <nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
      </li>
    <!--   <li class="nav-item d-none d-sm-inline-block">
        <a href="index3.html" class="nav-link">Home</a>
      </li>
      <li class="nav-item d-none d-sm-inline-block">
        <a href="#" class="nav-link">Contact</a>
      </li> -->
    </ul>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
     
      <!-- Notifications Dropdown Menu -->
      <li class="nav-item dropdown">
        <a class="nav-link" data-toggle="dropdown" href="#">
          <i class="far fa-bell"></i>
          <span class="badge badge-warning navbar-badge">0</span>
        </a>
        <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
          <span class="dropdown-item dropdown-header">0 Notifications</span>
          <!--<div class="dropdown-divider"></div>-->
          <!--<a href="#" class="dropdown-item">-->
          <!--  <i class="fas fa-envelope mr-2"></i> 4 new messages-->
          <!--  <span class="float-right text-muted text-sm">3 mins</span>-->
          <!--</a>-->
          <!--<div class="dropdown-divider"></div>-->
          <!--<a href="#" class="dropdown-item">-->
          <!--  <i class="fas fa-users mr-2"></i> 8 friend requests-->
          <!--  <span class="float-right text-muted text-sm">12 hours</span>-->
          <!--</a>-->
          <!--<div class="dropdown-divider"></div>-->
          <!--<a href="#" class="dropdown-item">-->
          <!--  <i class="fas fa-file mr-2"></i> 3 new reports-->
          <!--  <span class="float-right text-muted text-sm">2 days</span>-->
          <!--</a>-->
          <!--<div class="dropdown-divider"></div>-->
          <a href="#" class="dropdown-item dropdown-footer">See All Notifications</a>
        </div>
      </li>
      <li class="nav-item">
      <form method="POST" action="{{ route('logout') }}">
                            @csrf
          <a class="nav-link" href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();" role="button">Logout  
            <i class="fas fa-sign-out-alt"></i>
           </a>                                         
      </form>

      </li>
      
    </ul>
  </nav>
  <!-- /.navbar -->

  <!-- Main Sidebar Container -->
  <aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="{{route('dashboard')}}" class="brand-link" style="background-color: #fff;">
      <img src="{{ asset('image') }}/logo.png" alt="SB Logo" class="brand-image " >
      <!--<span class="brand-text font-weight-light">AdminLTE 3</span>-->
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
      <!-- Sidebar user panel (optional) -->
       <li style="list-style-type: none; margin: 0; padding: 0;" class="nav-item">
      <div class="user-panel mt-3 pb-3 mb-3 d-flex">
        <div class="image">
          <img src="{{ asset('asset') }}/dist/img/user2-160x160.jpg" class="img-circle elevation-2" alt="User Image">
        </div>
        <div class="info">
          <a href="#" class="d-block">{{ Auth::user()->name }}</a>
        </div>
      </div>

      </li>
     
      <!-- Sidebar Menu -->
      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
         
          <li class="nav-item ">
            <a href="{{route('dashboard')}}" class="nav-link{{ (request()->is('dashboard')) ? ' active' : '' }}">
              <i class="nav-icon fas fa-tachometer-alt"></i>
              <p>
                Dashboard
              </p>
            </a>
          </li>
            @can('users')
          <li class="nav-item ">
            <a href="{{route('user.index')}}" class="nav-link {{ (request()->is('user*')) ? 'active' : '' }}">
               <i class="nav-icon fas fa-copy"></i>
              <p>
                User Management
              </p>
            </a>
          </li>
          @endcan
          @can('booking')
          <li class="nav-item ">
            <a href="{{route('booking')}}" class="nav-link {{ (request()->is('booking*')) ? 'active' : '' }}">
              <i class="nav-icon fas fa-tree"></i>
              <p>
                Booking
              </p>
            </a>
          </li>
           <li class="nav-item ">
            <a href="{{route('client_api_logs')}}" class="nav-link {{ (request()->is('client_api_logs*')) ? 'active' : '' }}">
              <i class="nav-icon fas fa-tree"></i>
              <p>
                Waaree API Logs
              </p>
            </a>
          </li>
          @endcan
          @can('pincode')
          <li class="nav-item ">
            <a href="{{route('Pincode')}}" class="nav-link {{ (request()->is('Pincode*')) ? 'active' : '' }}">
              <i class="nav-icon fas fa-edit"></i>
              <p>
               Pincode
              </p>
            </a>
          </li>
          @endcan
          @can('aws')
           <li class="nav-item ">
            <a href="{{route('bulkreport')}}" class="nav-link {{ (request()->is('bulkreport*')) ? 'active' : '' }}">
          <i class="nav-icon fas fa-chart-pie"></i>
              <p>
              Multiple AWB Printing
              </p>
            </a>
          </li>
          @endcan
              @can('aws')
           <li class="nav-item ">
            <a href="{{route('bulksticker')}}" class="nav-link {{ (request()->is('bulksticker*')) ? 'active' : '' }}">
            <i class="nav-icon fas fa-chart-pie"></i>
              <p>
              Sticker Printing
              </p>
            </a>
          </li>
            <li class="nav-item ">
            <a href="{{route('misreport')}}" class="nav-link {{ (request()->is('misreport*')) ? 'active' : '' }}">
            <i class="nav-icon fas fa-chart-pie"></i>
              <p>
              MIS Report
              </p>
            </a>
          </li>
          @endcan
        @if(Auth::user()->id == 6)
          @can('pancard')
          <li class="nav-item ">
                <a href="{{route('user-pancard')}}" class="nav-link {{ (request()->is('bulkreport*')) ? 'active' : '' }}">
                <i class="nav-icon fas fa-chart-pie"></i>
              <p>
              Pancard
              </p>
            </a>
          </li>
         @endcan
         @endif

        </ul>
      </nav>
      <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
  </aside>

  @yield('content')
  <footer class="main-footer">
     <strong>Copyright &copy; 2022-2023 <a href="https://https://sbexpresscargo.com">SB Express Cargo LLP.</a></strong>
    All rights reserved.
    <div class="float-right d-none d-sm-inline-block">
      <b>Version</b> 0.0.1
    </div>
  </footer>

 
</div>
<!-- ./wrapper -->


<script src="{{ asset('asset') }}/plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="{{ asset('asset') }}/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- DataTables  & Plugins -->
<script src="{{ asset('asset') }}/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="{{ asset('asset') }}/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="{{ asset('asset') }}/plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="{{ asset('asset') }}/plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
<script src="{{ asset('asset') }}/plugins/datatables-buttons/js/dataTables.buttons.min.js"></script>
<script src="{{ asset('asset') }}/plugins/datatables-buttons/js/buttons.bootstrap4.min.js"></script>
<script src="{{ asset('asset') }}/plugins/jszip/jszip.min.js"></script>
<script src="{{ asset('asset') }}/plugins/pdfmake/pdfmake.min.js"></script>
<script src="{{ asset('asset') }}/plugins/pdfmake/vfs_fonts.js"></script>
<script src="{{ asset('asset') }}/plugins/datatables-buttons/js/buttons.html5.min.js"></script>
<script src="{{ asset('asset') }}/plugins/datatables-buttons/js/buttons.print.min.js"></script>
<script src="{{ asset('asset') }}/plugins/datatables-buttons/js/buttons.colVis.min.js"></script>
<!-- AdminLTE App -->
<script src="{{ asset('asset') }}/dist/js/adminlte.min.js"></script>
<!-- AdminLTE for demo purposes -->
<script src="{{ asset('asset') }}/dist/js/demo.js"></script>
<!-- bs-custom-file-input -->
<script src="{{ asset('asset') }}/plugins/bs-custom-file-input/bs-custom-file-input.min.js"></script>
<!-- SweetAlert2 -->
<script src="{{ asset('asset') }}/plugins/sweetalert2/sweetalert2.min.js"></script>
<script src="{{ asset('asset') }}/plugins/toastr/toastr.min.js"></script>
<!-- Page specific script -->
<script>
$(function () {
  bsCustomFileInput.init();
});
</script>
<script>
  $(function () {
    $("#example1").DataTable({
      "responsive": true, "lengthChange": false, "autoWidth": false,
      "buttons": ["csv", "excel", "pdf", "print"]
      // "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"]
    }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');
    $('#example2').DataTable({
      "paging": true,
      "lengthChange": false,
      "searching": false,
      "ordering": true,
      "info": true,
      "autoWidth": false,
      "responsive": true,
    });
  });
</script>
<script>
  $(function() {
    var Toast = Swal.mixin({
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 3000
    });
    @if(Session::has('alert-success'))
      toastr.success("{{Session::get('alert-success')}}")
    @endif
    @if(Session::has('alert-info'))
      toastr.info("{{Session::get('alert-info')}}")
    @endif
    @if(Session::has('alert-error'))
      toastr.error("{{Session::get('alert-error')}}")
    @endif
    @if(Session::has('alert-warning'))
      toastr.warning("{{Session::get('alert-warning')}}")
    @endif   
  });
</script>
 <script>
  function disableSubmitButton() {
    document.getElementById("submit-btn").disabled = true;
  }
</script>
</body>
</html>
