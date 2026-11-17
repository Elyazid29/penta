<?php

namespace App\Providers;

use App\Models\FormOrder;
use App\Models\FormPenawaran;
use App\Models\Invoice;
use App\Models\JadwalOperasional;
use App\Models\MasterArmada;
use App\Models\MasterBarang;
use App\Models\MasterKaryawan;
use App\Models\MasterKlien;
use App\Models\MasterPaket;
use App\Models\MasterPerusahaan;
use App\Models\SuratJalan;
use App\Models\TransaksiKeuangan;
use App\Models\User;
use App\Observers\AuditObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        RateLimiter::for('login', function (Request $request): Limit {
            $email = Str::lower($request->string('email')->toString());

            return Limit::perMinute(5)->by($email.'|'.$request->ip());
        });

        foreach ([
            User::class,
            MasterBarang::class,
            MasterPaket::class,
            MasterKaryawan::class,
            MasterKlien::class,
            MasterArmada::class,
            MasterPerusahaan::class,
            FormOrder::class,
            FormPenawaran::class,
            SuratJalan::class,
            Invoice::class,
            TransaksiKeuangan::class,
            JadwalOperasional::class,
        ] as $model) {
            $model::observe(AuditObserver::class);
        }
    }
}
