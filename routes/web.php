<?php

use App\Http\Controllers\Asset\ShowAssetController;
use App\Http\Controllers\Asset\ShowSiteAssetController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/pages');

Route::livewire('/settings', 'pages::settings')->name('settings.index');

Route::get('/assets/{filename}', ShowSiteAssetController::class)->name('site-assets.show');

Route::livewire('/pages/{path}/edit', 'pages::page.edit')->where('path', '.*')->name('pages.edit');

Route::get('/pages/{path}/assets/{filename}', ShowAssetController::class)->where('path', '.*')->name('assets.show');

Route::livewire('/directories/create', 'pages::page.create-directory')->name('directories.create');

Route::livewire('/pages/{directory?}', 'pages::page.index')->where('directory', '.*')->name('pages.index');
