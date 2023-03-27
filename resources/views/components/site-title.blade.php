<div class="row-mx-5 p-0 mb-0 xl-auto bg-warning">
<div class="col-xl-12 mx-auto">
    <div class="d-flex flex-col">
    
        <div class=" col-xl-2 w-35">
            <img src="{{ asset('img/logo.png') }}" id="logo" alt="maemaestore logo" class="img-fluid" height="100" width="100">
        </div>
        
        <div class=" col-xl-7">
        <h6 class="text-center">POINT OF SALES AND INVENTORY MANAGEMENT </h6>
            <h6 class="text-center"> SYSTEM</h6>
</div>
      
        <div class="col-xl-auto">
            <span><k class="mx-3">Admin: {{ Auth::user()->first_name }}</k> </span>
                <a href="{{ url('/logout') }}">
                 <k>   <i class="fa-solid fa-person mx-3 text-align:right"></i> Log out
                </a></k>
            </div>
        </div>
</div>
</div>
