<?php

use Inertia\Testing\AssertableInertia as Assert;

it('renders the editor page for a given id without DB', function () {
    $response = $this->get('/editor/123');

    $response->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('editor/[id]')
            ->where('id', '123')
        );
});
