<?php

use App\Http\Controllers\Attachment\ShowAttachmentController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/pages');

Route::livewire('/pages/{path}/edit', 'pages::page.edit')->where('path', '.*')->name('pages.edit');

Route::get('/pages/{path}/attachments/{filename}', ShowAttachmentController::class)->where('path', '.*')->name('attachments.show');

Route::livewire('/directories/create', 'pages::page.create-directory')->name('directories.create');

Route::livewire('/pages/{directory?}', 'pages::page.index')->where('directory', '.*')->name('pages.index');
