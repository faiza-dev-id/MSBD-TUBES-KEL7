<?php

use App\Support\Referensi\OpsiPpdb;

it('formats known, unknown, and empty PPDB option values safely', function (): void {
    expect(OpsiPpdb::label('hobi', 'A'))->toBe('Olahraga')
        ->and(OpsiPpdb::label('berkebutuhan_khusus', '01'))->toBe('Tunanetra')
        ->and(OpsiPpdb::label('agama', '01', ['01' => 'Islam']))->toBe('Islam')
        ->and(OpsiPpdb::label('hobi', 'Z'))->toBe('Z')
        ->and(OpsiPpdb::label('hobi', null))->toBe('-')
        ->and(OpsiPpdb::label('hobi', ''))->toBe('-');
});
