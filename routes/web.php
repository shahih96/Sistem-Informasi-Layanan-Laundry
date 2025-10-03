<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LandingController;

// === Admin Controllers (alias biar jelas) ===
use App\Http\Controllers\Admin\{
    DashboardController as AdminDashboardController,
    ServiceController,
    PesananController,
    StatusController,
    MetodePembayaranController,
    BonController,
    SaldoBonController,
    SaldoKartuController,
    SaldoKasController,
    FeeController,
    RekapController,
    InformasiLaundryController,
    PesananLaundryController,
    StatusPesananController
};

// --------------------
// Public pages
// --------------------
Route::get('/', [LandingController::class, 'home'])->name('landing.home');
Route::get('/services', [LandingController::class, 'services'])->name('services');
Route::get('/tracking', [LandingController::class, 'tracking'])->name('tracking');

// --------------------
// Auth (user) dashboard – kalau memang mau pakai dashboard admin juga, pakai controller admin
// --------------------
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
});

// --------------------
// Admin area
// --------------------
Route::prefix('admin')->name('admin.')->middleware(['auth','admin'])->group(function () {

    // dashboard
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    // services
    Route::resource('services', ServiceController::class);

    // pesanan & status
    Route::resource('pesanan', PesananLaundryController::class)->only(['index','store','update','destroy']);
    Route::resource('status', StatusPesananController::class)->only(['store','update','destroy']);

    // rekap
    Route::get('rekap', [RekapController::class,'index'])->name('rekap.index');
    Route::get('rekap/input', [RekapController::class,'input'])->name('rekap.input');

    Route::post('rekap', [RekapController::class,'store'])->name('rekap.store'); // omset
    Route::post('rekap/pengeluaran', [RekapController::class,'storePengeluaran'])->name('rekap.store-pengeluaran');
    Route::post('rekap/saldo', [RekapController::class,'storeSaldo'])->name('rekap.store-saldo');

    // Hapus satu baris rekap (pengeluaran atau omzet yang tidak di-group)
    Route::delete('rekap/{rekap}', [RekapController::class, 'destroy'])->name('rekap.destroy');

    // Hapus satu grup omzet (service_id + metode_pembayaran_id)
    Route::delete('rekap-group', [RekapController::class, 'destroyGroup'])->name('rekap.destroy-group');

    // rekap (resource untuk index/store omzet)
    Route::resource('rekap', RekapController::class)->only(['index','store']);

    // tambahan endpoint khusus pengeluaran & saldo:
    Route::post('/rekap/pengeluaran', [RekapController::class, 'storePengeluaran'])
        ->name('rekap.store-pengeluaran');

    Route::post('/rekap/saldo', [RekapController::class, 'storeSaldo'])
        ->name('rekap.store-saldo');

    // lainnya
    Route::resource('bon', BonController::class)->only(['index','store','update','destroy']);
    Route::resource('saldo-bon', SaldoBonController::class)->only(['index','show']);
    Route::resource('saldo-kartu', SaldoKartuController::class)->only(['index','store','update','destroy']);
    Route::resource('saldo-kas', SaldoKasController::class)->only(['index','update']);
    Route::resource('fee', FeeController::class)->only(['index','update']);

});

require __DIR__.'/auth.php';