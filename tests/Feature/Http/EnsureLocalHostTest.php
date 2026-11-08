<?php

test('serves requests to local hosts', function (string $host) {
    $this->get("http://{$host}/pages")
        ->assertOk();
})->with(['localhost:8000', '127.0.0.1', '[::1]:8000', 'LOCALHOST']);

test('forbids requests to an unknown host', function () {
    $this->get('http://attacker.example:8000/pages')
        ->assertForbidden()
        ->assertDontSee('csrf-token');
});

test('forbids livewire updates sent to an unknown host', function () {
    $this->post('http://attacker.example'.route('default-livewire.update', absolute: false))
        ->assertForbidden();
});

test('serves requests to the hosts of the configured urls', function (string $key) {
    config(["pluma.{$key}" => 'http://pluma.test:9000']);

    $this->get('http://pluma.test/pages')
        ->assertOk();
})->with(['editor_url', 'url']);

test('serves requests to the hosts listed in PLUMA_ALLOWED_HOSTS', function () {
    config(['pluma.allowed_hosts' => 'other.test, Pluma.Test']);

    $this->get('http://pluma.test/pages')
        ->assertOk();
});
