<?php

use App\Providers\AppServiceProvider;
use Flux\FluxServiceProvider;
use Laravel\Head\HeadServiceProvider;
use Livewire\LivewireServiceProvider;

return [
    LivewireServiceProvider::class,
    FluxServiceProvider::class,
    HeadServiceProvider::class,
    AppServiceProvider::class,
];
