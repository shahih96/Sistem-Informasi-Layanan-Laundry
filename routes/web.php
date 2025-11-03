<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LandingController;

// === Admin Controllers (alias biar jelas) ===
use App\Http\Controllers\Admin\{
    DashboardController as AdminDashboardController,
    ServiceController,
    BonController,
    SaldoBonController,
    SaldoKartuController,
    SaldoKasController,
    FeeController,
    RekapController,
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

    // Opening Kas (pakai path /admin/... tapi tetap di sini tidak apa-apa)
    Route::post('/admin/rekap/opening', [RekapController::class, 'storeOpening'])
        ->name('admin.rekap.store-opening');

    Route::patch('/admin/rekap/opening/lock', [RekapController::class, 'lockOpening'])
        ->name('admin.rekap.lock-opening');

    // ❌ HAPUS route migrasi bon di blok ini agar tidak bentrok nama
    // (dipindah ke blok admin di bawah)
});

// --------------------
// Admin area
// --------------------
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {

    // dashboard
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    // services
    Route::resource('services', ServiceController::class);

    // pesanan & status
    Route::resource('pesanan', PesananLaundryController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('status', StatusPesananController::class)->only(['store', 'update', 'destroy']);

    // rekap
    Route::get('rekap', [RekapController::class, 'index'])->name('rekap.index');
    Route::get('rekap/input', [RekapController::class, 'input'])->name('rekap.input');

    // omzet
    Route::post('rekap', [RekapController::class, 'store'])->name('rekap.store');

    // pengeluaran & saldo
    Route::post('rekap/pengeluaran', [RekapController::class, 'storePengeluaran'])->name('rekap.store-pengeluaran');
    Route::post('rekap/saldo', [RekapController::class, 'storeSaldo'])->name('rekap.store-saldo');

    // hapus baris/grup
    Route::delete('rekap/{rekap}', [RekapController::class, 'destroy'])->name('rekap.destroy');
    Route::delete('rekap-group', [RekapController::class, 'destroyGroup'])->name('rekap.destroy-group');

    // UPDATE METODE BON -> TUNAI/QRIS
    Route::patch('rekap/bon/{pesanan}', [RekapController::class, 'updateBonMetode'])
        ->name('rekap.update-bon');

    // --- Opening Bon Pelanggan (Migrasi) ---
    Route::post('pesanan/migrasi-bon', [PesananLaundryController::class, 'storeMigrasiBon'])
        ->name('pesanan.store-migrasi-bon');

    Route::patch('pesanan/migrasi-bon/lock', [PesananLaundryController::class, 'lockMigrasiBon'])
        ->name('pesanan.lock-migrasi-bon');

    // lainnya
    Route::resource('bon', BonController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('saldo-bon', SaldoBonController::class)->only(['index', 'show']);
    Route::resource('saldo-kartu', SaldoKartuController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('saldo-kas', SaldoKasController::class)->only(['index', 'update']);
    Route::resource('fee', FeeController::class)->only(['index', 'update']);
});

require __DIR__ . '/auth.php';
