@section('content_scripts')
    <script src="{{ asset('/js/hotkeys-js/dist/hotkeys.js') }}" type="module" defer></script>
    <script src="{{ asset('/js/scope/admin-hotkeys.js') }}" type="module" defer></script>
@endsection

<div class="my-xl-0 mx-xl-0 row overflow:hidden">
    <div class="gutter-div col-xl-1"></div>
    <div class="col-xl-auto align-self-center px-0">
    </div>
    @include('components.site-title')
    
</div>

<div class="row mb-0">
    <div class="rows-xl-auto mx-0 px-0 bg-info">
        <nav id="admin_nav" class="side-nav text-center">


            <a class="p-3 d-block {{ navActive('inventory') }}" href="{{ url('/inventory') }}" title="[F2]">
                <div class="icon-container rounded-circle mx-auto text-center">
                    <i class="fa-solid fa-box align-middle"></i>
                </div>
                <k>INVENTORY</k>
            </a>

            <a class="p-3 d-block {{ navActive('pos') }}" href="{{ url('/pos') }}" title="[F7]">
                <div class="icon-container rounded-circle mx-auto text-center">
                    <i class="fa-solid fa-cart-shopping align-middle"></i>
                </div>
                <k>POINT OF SALES</k>
            </a>

            <a class="p-3 d-block {{ navActive('report') }}" href="{{ url('/report') }}" title="[F3]">
                <div class="icon-container rounded-circle mx-auto text-center">
                    <i class="fa-solid fa-tags align-middle"></i>
                </div>
                <k>REPORT</k>
            </a>
            <a class="p-3 d-block {{ navActive('accounts') }}" href="{{ url('/accounts') }}" title="[F4]">
                <div class="icon-container rounded-circle mx-auto text-center">
                    <i class="fa-solid fa-user align-middle"></i>
                </div>
                <k>ACCOUNTS</k>
            </a>
           
           
        </nav>
    </div>
    <div class="row-xl-10">
        @yield('admin_content')
    </div>
</div>
