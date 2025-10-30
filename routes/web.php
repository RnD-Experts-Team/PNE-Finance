<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\QuickBooksReportController;
use App\Http\Controllers\QuickBooksController;
use App\Http\Middleware\CheckSecretHeader;


Route::get('/', function () {
    return view('welcome');
});

Route::get('/transaction-list-flattened-csv', [QuickBooksReportController::class, 'exportTransactionListFlattened'])
  ->middleware(CheckSecretHeader::class)
 ->name('transaction.list.flattened.csv');

Route::get('/balance-sheet-flattened', [QuickBooksReportController::class, 'fetchBalanceSheetFlattened'])
  ->middleware(CheckSecretHeader::class)
 ->name('balance.sheet.flattened');

 Route::get('/profit-loss-detail-csv', [QuickBooksReportController::class, 'fetchProfitAndLossDetail'])
 ->middleware(CheckSecretHeader::class)
     ->name('qbo.pldetail.csv');

     Route::get('/profit-loss-detail-csva', [QuickBooksReportController::class, 'fetchProfitAndLossDetailall'])
     ->middleware(CheckSecretHeader::class)
         ->name('qbo.pldetaila.csv');

Route::get('/eula', function () {
    return view('legal.eula');
})->name('eula');

Route::get('/privacy-policy', function () {
    return view('legal.privacy-policy');
})->name('privacy-policy');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/qbo/reports', [QuickBooksReportController::class, 'showReportsPage'])->name('qbo.reports');
    Route::post('/qbo/fetch-report', [QuickBooksReportController::class, 'fetchReportLive'])->name('qbo.fetch.report');

// Fetch QuickBooks Reports
    Route::get('/qbo/report/{reportName}', [QuickBooksReportController::class, 'fetchReport'])->name('qbo.report');

// View stored reports
//Route::get('/qbo/reports', [QuickBooksReportController::class, 'getStoredReports'])->name('qbo.reports');

    Route::get('/qbo/report-view/{reportName}', [QuickBooksReportController::class, 'viewReport'])->name('qbo.report.view');


    Route::get('/qbo/connect', [QuickBooksController::class, 'connect'])->name('qbo.connect');
    Route::get('/qbo/callback', [QuickBooksController::class, 'callback'])->name('qbo.callback');

    Route::post('/qbo/export-csv', [QuickBooksReportController::class, 'exportCsv'])->name('qbo.export.csv');
    Route::post('/qbo/disconnect', [QuickBooksController::class, 'disconnect'])->name('qbo.disconnect');




});



require __DIR__.'/auth.php';
