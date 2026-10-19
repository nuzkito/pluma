<?php

use App\Http\Controllers\Asset\ShowAssetController;
use App\Http\Controllers\Asset\ShowSiteAssetController;
use App\Http\Controllers\Asset\ShowTagAssetController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/pages');

Route::livewire('/settings', 'pages::settings')->name('settings.index');

Route::get('/assets/{filename}', ShowSiteAssetController::class)->name('site-assets.show');

Route::livewire('/pages/{path}/edit', 'pages::page.edit')->where('path', '.*')->name('pages.edit');

Route::get('/pages/{path}/assets/{filename}', ShowAssetController::class)->where('path', '.*')->name('assets.show');

Route::livewire('/tags', 'pages::tag.index')->name('tags.index');

Route::livewire('/tags/{id}/edit', 'pages::tag.edit')->name('tags.edit');

Route::get('/tags/{id}/assets/{filename}', ShowTagAssetController::class)->name('tags.assets.show');

Route::livewire('/types', 'pages::type.index')->name('types.index');

Route::livewire('/directories/create', 'pages::page.create-directory')->name('directories.create');

Route::livewire('/pages/{directory?}', 'pages::page.index')->where('directory', '.*')->name('pages.index');
