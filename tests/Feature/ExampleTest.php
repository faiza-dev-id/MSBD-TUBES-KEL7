<?php

test('halaman utama mengarahkan pengguna ke halaman masuk', function () {
    $response = $this->get('/');

    $response->assertRedirect('/masuk');
});