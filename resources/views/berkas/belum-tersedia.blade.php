<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Berkas belum tersedia</title>
    @vite(['resources/css/app.css'])
</head>
<body class="theme-background min-h-screen">
    <div class="flex min-h-screen items-center justify-center p-6">
        <div class="card max-w-md text-center">
            <h1 class="font-serif text-lg font-bold text-navy">Berkas belum tersedia</h1>
            <p class="mt-2 text-sm text-mute">
                Berkas <b>{{ $nama }}</b> tercatat di sistem, tetapi file fisiknya belum tersedia di penyimpanan lokal.
            </p>
            <p class="mt-2 text-xs text-mute">Hubungi Tata Usaha jika berkas seharusnya sudah diunggah.</p>
            <button type="button" class="btn btn-ghost mt-4"
                    onclick="window.parent.postMessage({ type: 'tutup-preview-berkas' }, window.location.origin)">
                Tutup pratinjau
            </button>
        </div>
    </div>
    <script>
        window.parent.postMessage({ type: 'berkas-tidak-tersedia' }, window.location.origin);
    </script>
</body>
</html>
