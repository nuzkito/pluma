<?php

use App\Http\Controllers\Attachment\ShowAttachmentController;
use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::page.index')->name('pages.index');
Route::livewire('/pages/{path}/edit', 'pages::page.edit')->name('pages.edit');

Route::get('/pages/{path}/attachments/{filename}', ShowAttachmentController::class)->name('attachments.show');
