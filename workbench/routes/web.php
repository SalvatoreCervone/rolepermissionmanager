<?php

use Illuminate\Support\Facades\Route;
use Workbench\App\Models\User;

/*
|--------------------------------------------------------------------------
| Workbench Web Routes (Preview/Demo)
|--------------------------------------------------------------------------
|
| These routes provide a simple authentication flow and demo application
| routes for testing the ACL admin panel and dynamic middleware guard.
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

// Demo application routes
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/invoices', fn() => 'Invoices List')->name('invoices.index');
    Route::get('/invoices/create', fn() => 'Create Invoice')->name('invoices.create');
    Route::delete('/invoices/{id}', fn($id) => "Invoice {$id} deleted")->name('invoices.destroy');

    Route::get('/posts', fn() => 'Posts List')->name('posts.index');
    Route::post('/posts', fn() => 'Post Created')->name('posts.store');
    Route::put('/posts/{id}/publish', fn($id) => "Post {$id} published")->name('posts.publish');

    Route::get('/reports', fn() => 'Reports View')->name('reports.view');
    Route::post('/reports/export', fn() => 'Export Reports')->name('reports.export');

    Route::get('/settings', fn() => 'Settings Page')->name('settings.manage');
});
