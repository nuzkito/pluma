<?php

use App\Providers\AppServiceProvider;

use Livewire\LivewireServiceProvider;
use Flux\FluxServiceProvider;

return [
    LivewireServiceProvider::class,
    FluxServiceProvider::class,
    AppServiceProvider::class,
];
