
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
                    <a href="{{ route('v2.home') }}" class="nav-link @if(request()->routeIs('v2.home')) active @endif">
                        <i class="nav-icon fas fa-home"></i>
                        <p>
                          Dashboard
                        </p>
                    </a>
                </li>
                <li class="nav-item menu-open">
                    <a href="{{ route('v2.vehicles.index') }}" class="nav-link @if(request()->routeIs('v2.vehicles.*')) active @endif
                        @if(request()->segment(1) == "vehicle") active @endif">
                        <i class="nav-icon fas fa-map"></i>
                        <p>
                           Own Vehicle
                        </p>
                    </a>
                </li>
                <li class="nav-item menu-open">
                    <a href="{{ route('v2.market-vehicles.index') }}" class="nav-link @if(request()->routeIs('v2.market-vehicles.*')) active @endif
                        @if(request()->segment(1) == "vehicleOther") active @endif">
                        <i class="nav-icon fas fa-map"></i>
                        <p>
                           Market Vehicle
                        </p>
                    </a>
                </li>
                <li class="nav-item menu-open">
                    <a href="{{ route('v2.lr-trackings.index') }}" class="nav-link @if(request()->routeIs('v2.lr-trackings.*')) active @endif
                        @if(request()->segment(1) == "lrtracking") active @endif">
                        <i class="nav-icon fas fa-truck"></i>
                        <p>
                            Lr Tracking
                        </p>
                    </a>
                </li>

                <li class="nav-item menu-open">
                    <a href="{{ route('v2.lr-trackings.completed') }}" class="nav-link @if(request()->routeIs('v2.lr-trackings.completed')) active @endif
                        @if(request()->segment(1) == "delivered_list") active @endif">
                        <i class="nav-icon fas fa-tasks"></i>
                        <p>
                            Lr Tracking Finished
                        </p>
                    </a>
                </li>
                <li class="nav-item menu-open">
                    <a href="{{ route('v2.weight-corrections.index') }}" class="nav-link @if(request()->routeIs('v2.weight-corrections.*')) active @endif
                        @if(request()->segment(1) == "weight-correction") active @endif">
                        <i class="nav-icon fa fa-weight-hanging"></i>
                        <p>
                           Add Weight
                        </p>
                    </a>
                </li>
                <li class="nav-item menu-open">
                    <a href="{{ route('v2.epods.index') }}" class="nav-link @if(request()->routeIs('v2.epods.*')) active @endif">
                        <i class="nav-icon fas fa-upload"></i>
                        <p>
                            Epod
                        </p>
                    </a>
                </li>
                <li class="nav-item menu-open">
                    <a href="{{ route('v2.settings.edit') }}" class="nav-link @if(request()->routeIs('v2.settings.*')) active @endif
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
                    <form id="logout-form" action="{{ route('v2.logout') }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                </li>
            </ul>
          </nav>



          <!-- /.sidebar-menu -->
        </div>
        <!-- /.sidebar -->
      </aside>
