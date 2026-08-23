<?php

use App\Http\Controllers\LeadController;
use App\Http\Controllers\MarketingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Central routes
|--------------------------------------------------------------------------
|
| Served on the CENTRAL domains only — africhartemr.com, www, and admin
| (config('tenancy.central_domains')). These never enter tenant context and
| always run against the central database.
|
| The clinic application lives in routes/tenant.php and is served on a
| clinic's own subdomain. Nothing clinical belongs here: there is no `staff`
| table and no patient data in the central database at all.
|
*/

// --- Public marketing site (root domain) ---
// These sit OUTSIDE the `guest` group on purpose: a logged-in staff member
// should still be able to read the pricing or privacy pages. The nav swaps
// "Sign in" for "Dashboard" based on auth state instead of redirecting.
Route::controller(MarketingController::class)->group(function () {
    Route::get('/', 'home')->name('home');
    Route::get('/features', 'features')->name('features');
    Route::get('/pricing', 'pricing')->name('pricing');
    Route::get('/about', 'about')->name('about');
    Route::get('/privacy', 'privacy')->name('legal.privacy');
    Route::get('/terms', 'terms')->name('legal.terms');
    Route::get('/data-processing', 'dataProcessing')->name('legal.dpa');
});

// Lead capture. `/signup` is a CLINIC requesting access — deliberately distinct
// from `/register`, which is a staff member joining an existing clinic with an
// invite code. Neither creates an account here; provisioning stays manual.
Route::controller(LeadController::class)->group(function () {
    Route::get('/demo', 'showDemo')->name('demo');
    Route::post('/demo', 'storeDemo')->middleware('throttle:5,1');
    Route::get('/signup', 'showSignup')->name('signup');
    Route::post('/signup', 'storeSignup')->middleware('throttle:5,1');
    Route::get('/contact', 'showContact')->name('contact');
    Route::post('/contact', 'storeContact')->middleware('throttle:5,1');
});
