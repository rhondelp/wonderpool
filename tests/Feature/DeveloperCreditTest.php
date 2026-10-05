<?php

/*
| change/developer-credits: developer credit in the public footer and the admin sidebar.
*/

use App\Models\User;

it('credits the developer in the public footer', function () {
    $this->get('/')->assertOk()
        ->assertSee('Website by')
        ->assertSee('Rhondel M. Pagobo')
        ->assertSee('mailto:rhondelpagobo19@gmail.com', false);
});

it('credits the developer in the admin sidebar', function () {
    $this->actingAs(User::factory()->create())->get('/admin')->assertOk()
        ->assertSee('Developed by')
        ->assertSee('Rhondel M. Pagobo');
});
