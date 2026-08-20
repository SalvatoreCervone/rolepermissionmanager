<?php

use Illuminate\Support\Facades\Route;
use Workbench\App\Models\User;

/*
|--------------------------------------------------------------------------
| Workbench Web Routes (Preview/Demo)
|--------------------------------------------------------------------------
|
| These routes provide a simple authentication flow for testing the
| ACL admin panel without needing a full Laravel application.
|
*/

// Simple login page for the preview.
Route::get('/login', function () {
    return view('acl::_preview.login');
})->name('login');

Route::post('/login', function () {
    $credentials = request()->only('email', 'password');

    if (auth()->attempt($credentials)) {
        request()->session()->regenerate();
        return redirect()->intended(config('rolepermissionmanager.admin_panel.prefix', 'acl-admin'));
    }

    return back()->withErrors(['email' => 'Invalid credentials. Try admin@demo.test / password']);
});

Route::post('/logout', function () {
    auth()->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/login');
})->name('logout');

// Redirect root to admin panel.
Route::get('/', function () {
    return redirect(config('rolepermissionmanager.admin_panel.prefix', 'acl-admin'));
});
