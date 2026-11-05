<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\FlockController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\BiodataController;
use App\Http\Controllers\OverviewController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\WeekEntryController;
use App\Http\Controllers\DailyEntryController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\WebsiteHomeController;

Route::get('/', [WebsiteHomeController::class, 'index'])->name('websiteHome');


Auth::routes();
Route::get('/home', [HomeController::class, 'index'])->name('home');

Route::group(['middleware' => ['auth']], function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    //Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
    Route::get('/dashboard/export', [DashboardController::class, 'export'])->name('dashboard.export');

    Route::resource('roles', RoleController::class);
    Route::resource('users', UserController::class);
    Route::delete('/users/{id}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::get('/users/{user}/overview', [UserController::class, 'overview'])->name('users.overview');
    Route::get('/users/roles', [UserController::class, 'roles']);
    Route::resource('permissions', PermissionController::class);


    Route::resource('biodata', BiodataController::class);
    Route::get('/overview/{id}', [OverviewController::class, 'show'])->name('user.overview');
    Route::get('/settings/{id}', [BiodataController::class, 'show'])->name('user.settings');

    Route::get('/adduser/{id}', [RoleController::class, 'adduser'])->name('roles.adduser');
    Route::post('/updateuserrole', [RoleController::class, 'updateuserrole'])->name('roles.updateuserrole');
    Route::delete('roles/removeuserrole/{userid}/{roleid}', [RoleController::class, 'removeuserrole'])->name('roles.removeuserrole');

    Route::resource('flocks', FlockController::class)->except(['create', 'show', 'edit']);

    Route::resource('expenses', ExpenseController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('feed', FeedController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');

    Route::prefix('flocks/{flock_id}')->group(function () {
        Route::get('weeks', [WeekEntryController::class, 'index'])->name('week-entries.index');
        Route::get('weeks/create', [WeekEntryController::class, 'create'])->name('week-entries.create');
        Route::post('weeks', [WeekEntryController::class, 'store'])->name('week-entries.store');
        Route::put('weeks/{week_id}', [WeekEntryController::class, 'update'])->name('week-entries.update');
        // 'bulk' must be declared before the {week_id} wildcard or it is captured as a week id.
        Route::delete('weeks/bulk', [WeekEntryController::class, 'bulkDestroy'])->name('week-entries.bulk-delete');
        Route::delete('weeks/{week_id}', [WeekEntryController::class, 'destroy'])->name('week-entries.delete');
    });

    // Use resource routing for daily-entries to include all standard CRUD routes
    Route::prefix('daily-entries/{weekId}')->group(function () {
    Route::get('/', [DailyEntryController::class, 'index'])->name('daily-entries.index');
    Route::post('/', [DailyEntryController::class, 'store'])->name('daily-entries.store');
    Route::put('/{id}', [DailyEntryController::class, 'update'])->name('daily-entries.update');
    Route::delete('/{id}', [DailyEntryController::class, 'destroy'])->name('daily-entries.delete');
    Route::get('/create', [DailyEntryController::class, 'create'])->name('daily-entries.create');
    Route::get('/{id}', [DailyEntryController::class, 'show'])->name('daily-entries.show');
});


});
