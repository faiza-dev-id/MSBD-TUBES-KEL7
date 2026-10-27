<?php

namespace App\Support;

use App\Models\Pengguna;
use Illuminate\Support\Facades\Auth;

/**
 * Siapa pengguna yang sedang bertindak pada request ini?
 * Login web menyimpan id_pengguna di session (bukan Auth::login), login API memakai token Sanctum.
 */
class AuditPelaku
{
    /** @return array{id:int,username:string}|null */
    public static function sekarang(): ?array
    {
        $req = request();
        if ($req->attributes->has('audit_pelaku')) {
            return $req->attributes->get('audit_pelaku');
        }

        $u = null;
        try {
            $u = Auth::user() ?? Auth::guard('sanctum')->user();
            if (!$u && $req->hasSession() && ($id = $req->session()->get('id_pengguna'))) {
                $u = Pengguna::query()->select('id_pengguna', 'username')->find($id);
            }
        } catch (\Throwable $e) {
            report($e);
        }

        $p = $u ? ['id' => (int) $u->id_pengguna, 'username' => (string) $u->username] : null;
        $req->attributes->set('audit_pelaku', $p);

        return $p;
    }
}
