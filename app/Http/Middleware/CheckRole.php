<?php
namespace App\Http\Middleware;
use Closure; use Illuminate\Http\Request; use Symfony\Component\HttpFoundation\Response;
class CheckRole { public function handle(Request $request, Closure $next, string ...$roles): Response { $u=$request->user(); if(!$u) return response()->json(['message'=>'Unauthenticated'],401); if(!$u->is_aktif || !$u->role || !in_array($u->role->nama_role,$roles,true)) return response()->json(['message'=>'Forbidden'],403); return $next($request); } }
