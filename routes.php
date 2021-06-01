<?php

use EvolutionCMS\Directory\Controller;
use Illuminate\Support\Facades\Route;

Route::get('', [Controller::class, 'index'])
    ->name('directory::index');

Route::get('show/{document}/{folder?}', [Controller::class, 'show'])
    ->whereNumber('document')
    ->whereNumber('folder')
    ->name('directory::show');

Route::post('action', [Controller::class, 'action'])
    ->name('directory::action');

