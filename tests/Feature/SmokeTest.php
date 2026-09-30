<?php

/*
| M0 smoke tests: the app boots, the public layout renders, and the
| local-only design preview is not exposed outside the local environment.
*/

it('renders the home page with the public layout', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee(config('app.name'))
        ->assertSee('Skip to content');
});

it('does not expose the design preview outside local', function () {
    $this->get('/design-preview')->assertNotFound();
});
