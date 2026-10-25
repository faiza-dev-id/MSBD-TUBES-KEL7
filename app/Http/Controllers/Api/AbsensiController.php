<?php
namespace App\Http\Controllers\Api; use App\Http\Controllers\Controller; use App\Services\AbsensiService; use Illuminate\Http\Request;
class AbsensiController extends Controller { public function __construct(private AbsensiService $service){} public function rekap(Request $r){abort_unless($r->user()->hasPermission('akademik.manage'),403);$d=$r->validate(['id_semester'=>'required|integer']);return response()->json($this->service->rekap((int)$d['id_semester']));} }
