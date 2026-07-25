<?php

use App\Http\Controllers\Asset\ShowAssetController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/pages');

Route::livewire('/settings', 'pages::settings')->name('settings.index');

Route::livewire('/pages/{path}/edit', 'pages::page.edit')->where('path', '.*')->name('pages.edit');

Route::get('/pages/{path}/assets/{filename}', ShowAssetController::class)->where('path', '.*')->name('assets.show');

Route::livewire('/directories/create', 'pages::page.create-directory')->name('directories.create');

Route::livewire('/pages/{directory?}', 'pages::page.index')->where('directory', '.*')->name('pages.index');
