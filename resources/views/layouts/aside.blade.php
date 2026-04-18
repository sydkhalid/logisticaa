
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
              <a href="#" class="d-block">{{ $set['name'] }}</a>
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
                    <a href="{{ route('vehicle.index') }}" class="nav-link @if(request()->segment(1) == "vehicle") active @endif
                        @if(request()->segment(1) == "vehicle") active @endif">
                        <i class="nav-icon fas fa-map"></i>
                        <p>
                           Own Vehicle
                        </p>
                    </a>
                </li>
                <li class="nav-item menu-open">
                    <a href="{{ route('vehicleOther.index') }}" class="nav-link @if(request()->segment(1) == "vehicleOther") active @endif
                        @if(request()->segment(1) == "vehicleOther") active @endif">
                        <i class="nav-icon fas fa-map"></i>
                        <p>
                           Market Vehicle
                        </p>
                    </a>
                </li>
                <li class="nav-item menu-open">
                    <a href="{{ url('/lrtracking') }}" class="nav-link @if(request()->segment(1) == "lrtracking") active @endif
                        @if(request()->segment(1) == "lrtracking") active @endif">
                        <i class="nav-icon fas fa-truck"></i>
                        <p>
                            Lr Tracking
                        </p>
                    </a>
                </li>

                <li class="nav-item menu-open">
                    <a href="{{ url('/delivered_list') }}" class="nav-link @if(request()->segment(1) == "delivered_list") active @endif
                        @if(request()->segment(1) == "delivered_list") active @endif">
                        <i class="nav-icon fas fa-tasks"></i>
                        <p>
                            Lr Tracking Finished
                        </p>
                    </a>
                </li>
                <li class="nav-item menu-open">
                    <a href="{{ url('/weight-correction') }}" class="nav-link @if(request()->segment(1) == "weight-correction") active @endif
                        @if(request()->segment(1) == "weight-correction") active @endif">
                        <i class="nav-icon fa fa-weight-hanging"></i>
                        <p>
                           Add Weight
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
                    <a href="{{ url('/settings') }}" class="nav-link @if(request()->segment(1) == "settings") active @endif
                        @if(request()->segment(1) == "settings") active @endif">
                        <i class="nav-icon fas fa-cogs"></i>
                        <p>
                            Setting
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
