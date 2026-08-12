<?php

arch('the domain does not depend on the delivery layers')
    ->expect('App\Domain')
    ->not->toUse(['App\Http', 'Livewire']);

arch('the generator does not depend on the editor')
    ->expect('App\Domain\Generator')
    ->not->toUse('App\Domain\Editor');

arch('no debugging statements are left behind')
    ->expect(['dd', 'ddd', 'dump', 'ray', 'var_dump', 'print_r'])
    ->not->toBeUsed();
