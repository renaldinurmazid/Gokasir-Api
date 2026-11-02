<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    /** Area /admin memakai root view terpisah (bundle & CSS sendiri). */
    public function rootView(Request $request): string
    {
        return $request->is('admin', 'admin/*') ? 'admin' : $this->rootView;
    }

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => fn () => ['admin' => ($a = $request->user('admin')) ? [
                'id' => $a->id, 'name' => $a->name, 'email' => $a->email,
            ] : null],
            // Badge sidebar: di-cache 5 menit agar tidak menghitung ulang di setiap halaman.
            'navCounts' => fn () => $request->user('admin')
                ? \Illuminate\Support\Facades\Cache::remember('admin:nav-counts', 300, fn () => app(\App\Services\Admin\PlatformStats::class)->navCounts())
                : null,
            'flash' => fn () => [
                'success'       => $request->session()->get('success'),
                'error'         => $request->session()->get('error'),
                'impersonation' => $request->session()->get('impersonation'),
            ],
        ];
    }
}
