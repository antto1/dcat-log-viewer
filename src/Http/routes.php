<?php

use Antto\LogViewer\Http\Controllers;
use Illuminate\Support\Facades\Route;

Route::get('log-viewer/{file?}', Controllers\DcatLogViewerController::class . '@index')->name('dcat-log-viewer.index');
