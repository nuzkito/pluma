<?php

use App\Providers\AppServiceProvider;
use Flux\FluxServiceProvider;
use Livewire\LivewireServiceProvider;

return [
    LivewireServiceProvider::class,
    FluxServiceProvider::class,
    AppServiceProvider::class,
];
