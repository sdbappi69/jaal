<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['namespace' => 'Api', 'prefix' => 'v1', 'middleware' => ['api_token']], function () {
    Route::resource('pages', 'PagesController')->except(['create', 'show', 'edit']);
    Route::resource('blogs', 'BlogsController')->except(['create', 'show', 'edit']);
    Route::resource('categories', 'CategoriesController')->except(['create', 'show', 'edit']);
    Route::resource('testimonials', 'TestimonialsController')->except(['create', 'show', 'edit']);
    Route::resource('terms', 'TermsController')->except(['create', 'show', 'edit']);
    Route::resource('products', 'ProductsController')->except(['create', 'show', 'edit']);
    Route::resource('coupons', 'CouponsController')->except(['create', 'show', 'edit']);
    Route::resource('customers', 'CustomersController')->except(['create', 'show', 'edit']);
    Route::resource('product-reviews', 'ReviewsController')->except(['create', 'show', 'edit']);
    Route::resource('reports', 'ReportsController')->except(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    Route::resource('settings', 'SettingsController')->except(['show', 'create', 'store', 'edit', 'update', 'destroy']);
});


