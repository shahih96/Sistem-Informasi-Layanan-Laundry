<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\InformasiLaundry;
use App\Models\PesananLaundry;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class LandingController extends Controller
{
    public function home()
    {
        $services = InformasiLaundry::latest()->take(6)->get();
        $wamsg = "Halo Qxpress Laundry! Saya mau pesan layanan Laundry";
        $waUrl = 'https://wa.me/6281373820217?text=' . rawurlencode($wamsg);
    
        return view('welcome', compact('services', 'waUrl'));
    }

    public function services()
    {
        $items = Service::select('id','nama_service','harga_service','updated_at')
        ->orderBy('nama_service')
        ->get();

        $rules = [
            'Paket Express' => [
                '/\b((3Kg)|(5Kg)|(7Kg)|kilat|exp)\b/i', '/\b(lipat|setrika)\b/i',
            ],
            'Setrika' => [
                '/^(?!.*\bcuci\b).*?\bsetrika\b/i',
            ],
            'Paket Regular' => [
                '/\b(regular|reguler)\b/i', '/\b(lipat|setrika)\b/i',
            ],
            'Self Service' => [
                '/\b(self|mandiri)\b|kering|dryer/i',
            ],
            'Bed Cover' => [
                '/bed\s*cover/i',
            ],
            'Hordeng' => [
                '/hord(e)?ng|gorden/i',
            ],
            'Antar Jemput' => [
                '/antar|jemput|pickup|delivery/i',
            ],
            'Add-on'=> [
                '/deterjen|pewangi|proclin|plastik|fee/i'
            ]
        ];

        $grouped = $items->groupBy(function ($i) use ($rules) {
            $name = Str::of($i->nama_service ?? '')->lower()->squish()->value();
            foreach ($rules as $label => $patterns) {
                $patterns = (array) $patterns;
                $ok = true;
                foreach ($patterns as $p) {
                    if (!preg_match($p, $name)) { $ok = false; break; }
                }
                if ($ok) return $label;
            }
            return 'Lainnya';
        });

        $order = [
            'Self Service',
            'Paket Regular',
            'Paket Express',
            'Setrika',
            'Bed Cover',
            'Hordeng',
            'Antar Jemput',
            'Add-on',
            'Lainnya'
        ];

        $grouped = collect($order)
            ->mapWithKeys(fn($k) => [$k => $grouped->get($k, collect())])
            ->filter(fn($rows) => $rows->isNotEmpty());
        $wamsg = "Halo Qxpress Laundry! Saya mau pesan layanan Laundry";
        $waUrl = 'https://wa.me/6281373820217?text=' . rawurlencode($wamsg);

        return view('daftarharga', compact('grouped', 'waUrl'));
    }

    public function tracking(Request $request)
    {
        $q = (string) $request->query('q', '');
        $items = collect();
        $error = null;
    
        $digits = preg_replace('/\D+/', '', $q);
    
        $local = Str::startsWith($digits, '62') ? '0'.substr($digits, 2) : $digits;
    
        $len = strlen($local);
        $valid = $len >= 11 && $len <= 13;
    
        if ($q !== '' && $valid) {
            $request->validate(
                ['g-recaptcha-response' => ['required', 'captcha']],
                [
                    'g-recaptcha-response.required' => 'Mohon centang captcha dulu ya.',
                    'g-recaptcha-response.captcha'  => 'Verifikasi captcha gagal, coba lagi.',
                ]
            );
    
            $intl = '+62'.ltrim($local, '0');
    
            $items = PesananLaundry::with(['service','metode','latestStatusLog.status'])
            ->where('no_hp_pel', 'like', "%{$q}%")
            ->where('is_hidden', false)
            ->latest()
            ->get();
    
        } elseif ($q !== '') {
            $error = 'Nomor HP yang Anda masukkan tidak valid.';
        }
    
        $wamsg = "Halo Qxpress Laundry! Saya mau pesan layanan Laundry";
        $waUrl = 'https://wa.me/6281373820217?text=' . rawurlencode($wamsg);
    
        return view('tracking', compact('q', 'items', 'error', 'waUrl'));
    }
}