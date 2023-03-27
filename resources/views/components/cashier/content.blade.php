@section('content_scripts')
    <script src="{{ asset('/js/hotkeys-js/dist/hotkeys.js') }}" type="module" defer></script>
    <script src="{{ asset('/js/scope/cashier-hotkeys.js') }}" type="module" defer></script>
@endsection
<div class="my-xl-0 mx-xl-0 row overflow:hidden">
    <div class="gutter-div col-xl-1"></div>
    <div class="col-xl-auto align-self-center px-0">
    </div>
    @include('components.site-title')
    
</div>

<div class="row mb-3">
    <div class="rows-xl-auto mx-2 px-0 bg-info">
        <nav id="cashier_nav" class="side-nav text-center">
            
            <a class="p-3 d-block {{ navActive('cashier-products') }}" href="{{ url('/cashier-products') }}"
                title="[F2]">
                <div class="icon-container rounded-circle mx-auto text-center">
                    <i class="fa-solid fa-box align-middle"></i>
                </div>
                <k>PRODUCTS</k>
            </a>

            <a class="p-3 d-block {{ navActive('pos') }}" href="{{ url('/pos') }}" title="[F1]">
                <div class="icon-container rounded-circle mx-auto text-center">
                    <i class="fa-solid fa-clipboard-list align-middle"></i>
                </div>
                <k>POINT OF SALES</k>
            </a>


            <a class="p-3 d-block {{ navActive('cashier-settings') }}" href="{{ url('/cashier-settings') }}"
                title="[F3]">
                <div class="icon-container rounded-circle mx-auto text-center">
                    <i class="fa-solid fa-tags align-middle"></i>
                </div>
                <k>EDIT ACCOUNT</k>
            </a>
           
        </nav>
    </div>
    <div class="row-xl-10">
        @yield('cashier_content')
    </div>
</div>
