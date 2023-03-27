@php
use App\Http\Controllers\UserController;
@endphp
<div class="align-items-center d-flex justify-content-center vh-100">
    <div>

        <div class="text-center mb-xl-4 w-50 mx-auto">
            <img src="{{ asset('img/logo.png') }}" id="logo" alt="maemaestore logo" class="img-fluid">
        </div>
        <div class="text-center mb-xl-4 w-50 mx-auto">
        @if (session('msg_error'))
            <div class="text-primary bg-danger p-xl-3 mb-xl-3 rounded-1">
                {{ session('msg_error') }}
            </div>
        @endif
</div>
        <div class="text-center mb-xl-4 w-50 mx-auto">
        <form action="{{ action([UserController::class, 'login']) }}" method="POST">
            @csrf
            <input name="username" class="form-control form-control-xl mb-xl-3" type="text" placeholder="Username"
                aria-label="username">
            <input name="password" class="form-control form-control-xl" type="password" placeholder="Password"
                aria-label="password">
            <input type="submit" class="form-control form-control-xl mt-3 py-xl-2" value="Log in">
        </form>
    
    </div>
</div>
