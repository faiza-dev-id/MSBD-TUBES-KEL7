<?php
namespace App\Http\Controllers\Api; use App\Http\Controllers\Controller; use App\Services\RaporService; use Illuminate\Http\Request;
class RaporController extends Controller { public function __construct(private RaporService $service){} public function nilaiAkhir(Request $r){abort_unless($r->user()->hasPermission('akademik.manage'),403);$d=$r->validate(['id_anggota'=>'required|integer','id_mapel'=>'required|integer','id_semester'=>'required|integer']);return response()->json($this->service->nilaiAkhir((int)$d['id_anggota'],(int)$d['id_mapel'],(int)$d['id_semester']));} }
