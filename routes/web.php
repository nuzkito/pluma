<?php

use App\Http\Controllers\Attachment\DeleteAttachmentController;
use App\Http\Controllers\Attachment\ShowAttachmentController;
use App\Http\Controllers\Attachment\UploadAttachmentController;
use App\Http\Controllers\Page\CreateDraftController;
use App\Http\Controllers\Page\DeletePageController;
use App\Http\Controllers\Page\EditPageController;
use App\Http\Controllers\Page\ListPagesController;
use App\Http\Controllers\Page\PublishPageController;
use App\Http\Controllers\Page\UnpublishPageController;
use App\Http\Controllers\Page\UpdatePageController;
use Illuminate\Support\Facades\Route;

Route::get('/', ListPagesController::class)->name('pages.index');
Route::post('/pages', CreateDraftController::class)->name('pages.store');
Route::get('/pages/{path}/edit', EditPageController::class)->name('pages.edit');
Route::put('/pages/{path}', UpdatePageController::class)->name('pages.update');
Route::post('/pages/{path}/publish', PublishPageController::class)->name('pages.publish');
Route::post('/pages/{path}/unpublish', UnpublishPageController::class)->name('pages.unpublish');
Route::delete('/pages/{path}', DeletePageController::class)->name('pages.destroy');
Route::post('/pages/{path}/attachments', UploadAttachmentController::class)->name('attachments.upload');
Route::get('/pages/{path}/attachments/{filename}', ShowAttachmentController::class)->name('attachments.show');
Route::delete('/pages/{path}/attachments/{filename}', DeleteAttachmentController::class)->name('attachments.delete');
