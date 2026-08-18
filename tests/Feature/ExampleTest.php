<?php

test('root redirects guests to login', function (): void {
    $this->get('/')->assertRedirect(route('login'));
});
