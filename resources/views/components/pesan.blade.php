@if (session('success'))
    <div class="mb-4 rounded-xl border border-ok/40 bg-ok/10 px-4 py-3 text-sm text-ok" role="status">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="mb-4 rounded-xl border border-bad/40 bg-bad/10 px-4 py-3 text-sm text-bad" role="alert">{{ session('error') }}</div>
@endif
@if ($errors->any())
    <div class="mb-4 rounded-xl border border-bad/40 bg-bad/10 px-4 py-3 text-sm text-bad" role="alert">
        <b>Periksa kembali isianmu:</b>
        <ul class="mt-1 list-disc pl-5">
            @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
@endif