{{-- Ringkasan error validasi dari server. Taruh di bawah @csrf pada setiap form: <x-galat />
     Di form bergrid beri lebar penuh: <x-galat class="lg:col-span-2" /> --}}
@if ($errors->any())
    <div role="alert" {{ $attributes->merge(['class' => 'rounded-xl border border-bad/30 bg-bad/10 p-3.5 text-sm text-bad']) }}>
        <b>Data belum bisa disimpan:</b>
        <ul class="mt-1 list-disc pl-5">
            @foreach ($errors->all() as $pesan)
                <li>{{ $pesan }}</li>
            @endforeach
        </ul>
    </div>
@endif