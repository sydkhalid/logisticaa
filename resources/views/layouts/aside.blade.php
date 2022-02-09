
      <!-- Main Sidebar Container -->
      <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <!-- Sidebar -->
        <div class="sidebar">
         <!-- Sidebar user panel (optional) -->
         <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="image">
              {{-- <img src="{{asset('img/user7-128x128.jpg')}}" class="img-circle elevation-2" alt="User Image"> --}}
            </div>
            <div class="info">
              <a href="#" class="d-block">Logisticaa</a>
            </div>
          </div>
          @php
             $url = !empty(request()->segment(2)) ? request()->segment(1).'/'.request()->segment(2) : request()->segment(1)
          @endphp
          <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                <li class="nav-item menu-open">
                    <a href="{{ url('home') }}" class="nav-link @if(request()->segment(1) == "home") active @endif">
                        <i class="nav-icon fas fa-home"></i>
                        <p>
                          Dashboard
                        </p>
                    </a>
                </li>
                <li class="nav-item menu-open">
                    <a href="{{ url('/lrtracking') }}" class="nav-link @if(request()->segment(1) == "lrtracking") active @endif
                        @if(request()->segment(1) == "tracking_show") active @endif">
                        <i class="nav-icon fas fa-truck"></i>
                        <p>
                            Lr Tracking
                        </p>
                    </a>
                </li>
                <li class="nav-item menu-open">
                    <a href="{{ url('/epod') }}" class="nav-link @if(request()->segment(1) == "epod") active @endif">
                        <i class="nav-icon fas fa-upload"></i>
                        <p>
                            Epod
                        </p>
                    </a>
                </li>
                <li class="nav-item menu-open">
                    <a href="javascript:void" onclick="$('#logout-form').submit();" class="nav-link">
                        <i class="nav-icon fas fa-sign-out-alt"></i>
                        <p>
                          Logout
                        </p>
                    </a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                </li>
            </ul>
          </nav>



          <!-- /.sidebar-menu -->
        </div>
        <!-- /.sidebar -->
      </aside>
