<?php
namespace App\Http\Middleware;
use Closure; use Illuminate\Http\Request; use Symfony\Component\HttpFoundation\Response;
class CheckPermission { public function handle(Request $request, Closure $next, string $code): Response { $u=$request->user(); if(!$u) return response()->json(['message'=>'Unauthenticated'],401); if(!method_exists($u,'hasPermission') || !$u->hasPermission($code)) return response()->json(['message'=>'Forbidden'],403); return $next($request); } }
