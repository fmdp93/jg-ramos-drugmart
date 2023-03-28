<div class="col-xl-12">
    @yield('heading_before')
    @include('components.success-message')    
    <h2 class="py-xl-4">{{ $heading }}</h2>
    @yield('heading_after')
</div>
