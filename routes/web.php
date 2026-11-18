<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\FlockController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\SalePaymentController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\BiodataController;
use App\Http\Controllers\OverviewController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\WeekEntryController;
use App\Http\Controllers\DailyEntryController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\WebsiteHomeController;
use App\Http\Controllers\PoultryController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\LivestockTypeController;
use App\Http\Controllers\LivestockController;
use App\Http\Controllers\ReportController;

Route::get('/', [WebsiteHomeController::class, 'index'])->name('websiteHome');


Auth::routes(['register' => config('poultrilic.allow_registration', true), 'verify' => true]);
Route::get('/home', fn () => redirect(\App\Support\Landing::for(auth()->user())))->middleware('auth')->name('home');
Route::view('/welcome', 'auth.pending')->middleware('auth')->name('account.pending');

Route::group(['middleware' => ['auth']], function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    //Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
    Route::get('/dashboard/export', [DashboardController::class, 'export'])->name('dashboard.export');

    Route::post('roles/bulk-remove-users', [RoleController::class, 'bulkRemoveUsers'])->name('roles.bulkremoveusers');
    Route::get('roles/{role}/users', [RoleController::class, 'getRoleUsers'])->name('roles.users');
    Route::resource('roles', RoleController::class);
    Route::post('users/{user}/reset-link', [UserController::class, 'sendResetLink'])->name('users.reset-link');
    Route::resource('users', UserController::class);
    Route::delete('/users/{id}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::get('/users/{user}/overview', [UserController::class, 'overview'])->name('users.overview');
    Route::get('/users/roles', [UserController::class, 'roles']);
    Route::resource('permissions', PermissionController::class);


    Route::resource('biodata', BiodataController::class);
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::post('/update-info', [BiodataController::class, 'updateProfile'])->name('update-info');
        Route::post('/update-avatar', [BiodataController::class, 'updateAvatar'])->name('update-avatar');
        Route::post('/update-email', [BiodataController::class, 'ajaxemailupdate'])->name('update-email');
        Route::post('/update-password', [BiodataController::class, 'ajaxpasswordupdate'])->name('update-password');
    });
    Route::get('/overview/{id}', [OverviewController::class, 'show'])->name('user.overview');
    Route::get('/settings/{id}', [BiodataController::class, 'show'])->name('user.settings');

    Route::get('/adduser/{id}', [RoleController::class, 'adduser'])->name('roles.adduser');
    Route::post('/updateuserrole', [RoleController::class, 'updateuserrole'])->name('roles.updateuserrole');
    Route::delete('roles/removeuserrole/{userid}/{roleid}', [RoleController::class, 'removeuserrole'])->name('roles.removeuserrole');

    Route::resource('flocks', FlockController::class)->except(['create', 'show', 'edit']);

    Route::resource('expenses', ExpenseController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('customers', CustomerController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('sales', SaleController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
    Route::get('sales/{sale}/invoice', [SaleController::class, 'invoice'])->name('sales.invoice');
    Route::get('sales/{sale}/payments/{payment}/receipt', [SaleController::class, 'receipt'])->name('sales.receipt');
    Route::post('sales/{sale}/payments', [SalePaymentController::class, 'store'])->name('sales.payments.store');
    Route::delete('sales/{sale}/payments/{payment}', [SalePaymentController::class, 'destroy'])->name('sales.payments.destroy');
    Route::resource('feed', FeedController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');

    // Spotlight (⌘K) search
    Route::get('search', \App\Http\Controllers\SearchController::class)->name('search');

    // Poultry: farm overview, flock pages, merge, recalculation and record export
    Route::get('overview', [PoultryController::class, 'overview'])->name('poultry.overview');
    Route::get('poultry/flocks/{flock}', [PoultryController::class, 'show'])->name('poultry.flocks.show');
    Route::get('poultry/flocks/{flock}/export', [PoultryController::class, 'export'])->name('poultry.flocks.export');
    Route::post('poultry/flocks/{flock}/recalculate', [PoultryController::class, 'recalculate'])->name('poultry.flocks.recalculate');
    Route::get('poultry/merge', [PoultryController::class, 'mergeForm'])->name('poultry.merge');
    Route::post('poultry/merge', [PoultryController::class, 'merge'])->name('poultry.merge.store');

    // Excel imports (poultry and every livestock type), templates and history
    Route::get('imports', [ImportController::class, 'index'])->name('imports.index');
    Route::get('imports/template/{kind}', [ImportController::class, 'template'])->name('imports.template');
    Route::post('imports/preview', [ImportController::class, 'preview'])->name('imports.preview');
    Route::get('imports/{batch}', [ImportController::class, 'show'])->name('imports.show');
    Route::post('imports/{batch}/commit', [ImportController::class, 'commit'])->name('imports.commit');

    // Livestock builder (admin) and recording
    Route::get('livestock/setup', [LivestockTypeController::class, 'index'])->name('livestock.types.index');
    Route::post('livestock/setup', [LivestockTypeController::class, 'store'])->name('livestock.types.store');
    Route::get('livestock/setup/{type}', [LivestockTypeController::class, 'edit'])->name('livestock.types.edit');
    Route::put('livestock/setup/{type}', [LivestockTypeController::class, 'update'])->name('livestock.types.update');
    Route::delete('livestock/setup/{type}', [LivestockTypeController::class, 'destroy'])->name('livestock.types.destroy');
    Route::post('livestock/setup/{type}/fields', [LivestockTypeController::class, 'storeField'])->name('livestock.fields.store');
    Route::put('livestock/setup/{type}/fields/{field}', [LivestockTypeController::class, 'updateField'])->name('livestock.fields.update');
    Route::delete('livestock/setup/{type}/fields/{field}', [LivestockTypeController::class, 'destroyField'])->name('livestock.fields.destroy');

    Route::get('livestock/{type}', [LivestockController::class, 'index'])->name('livestock.index');
    Route::post('livestock/{type}/units', [LivestockController::class, 'storeUnit'])->name('livestock.units.store');
    Route::get('livestock/{type}/units/{unit}', [LivestockController::class, 'show'])->name('livestock.units.show');
    Route::put('livestock/{type}/units/{unit}', [LivestockController::class, 'updateUnit'])->name('livestock.units.update');
    Route::delete('livestock/{type}/units/{unit}', [LivestockController::class, 'destroyUnit'])->name('livestock.units.destroy');
    Route::get('livestock/{type}/export', [LivestockController::class, 'export'])->name('livestock.export');
    Route::post('livestock/{type}/units/{unit}/records', [LivestockController::class, 'storeRecord'])->name('livestock.records.store');
    Route::delete('livestock/{type}/units/{unit}/records/{record}', [LivestockController::class, 'destroyRecord'])->name('livestock.records.destroy');

    // Reports: performance report and admin-defined reports
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/performance', [ReportController::class, 'performance'])->name('reports.performance');
    Route::get('reports/create', [ReportController::class, 'create'])->name('reports.create');
    Route::post('reports', [ReportController::class, 'store'])->name('reports.store');
    Route::get('reports/{report}', [ReportController::class, 'show'])->name('reports.show');
    Route::get('reports/{report}/edit', [ReportController::class, 'edit'])->name('reports.edit');
    Route::put('reports/{report}', [ReportController::class, 'update'])->name('reports.update');
    Route::delete('reports/{report}', [ReportController::class, 'destroy'])->name('reports.destroy');
    Route::get('reports/{report}/export/{format}', [ReportController::class, 'export'])->name('reports.export');

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
