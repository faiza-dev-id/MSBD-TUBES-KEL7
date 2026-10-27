<?php

namespace App\Http\Middleware;

use App\Support\AuditPelaku;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Trigger PostgreSQL fn_audit_trail (siswa, pengguna, pengajuan_perubahan) membaca
 * app.user_id dan app.username. Middleware ini mengisinya agar kolom pengubah tidak kosong.
 */
class SetAuditContext
{
    public function handle(Request $request, Closure $next): Response
    {
        if (DB::getDriverName() === 'pgsql' && ($p = AuditPelaku::sekarang())) {
            try {
                DB::select("select set_config('app.user_id', ?, false), set_config('app.username', ?, false)",
                    [(string) $p['id'], $p['username']]);
                $request->attributes->set('audit_ctx_set', true);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $next($request);
    }

    /** Bersihkan supaya koneksi yang dipakai ulang tidak membawa identitas request sebelumnya. */
    public function terminate(Request $request, Response $response): void
    {
        if (!$request->attributes->get('audit_ctx_set')) return;
        try {
            DB::select("select set_config('app.user_id', '', false), set_config('app.username', '', false)");
        } catch (\Throwable $e) {
            // koneksi sudah ditutup: aman diabaikan
        }
    }
}
