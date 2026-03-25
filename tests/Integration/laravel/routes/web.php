<?php

use App\Http\Controllers\ErrorsController;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Appsignal\Appsignal;

Route::get('/', function () {});

Route::post('/', function () {});

Route::get('/instrument', function () {
    Appsignal::instrument(
        'my-span',
        [
            'string-attribute' => 'abcdef',
            'int-attribute' => 1234,
            'bool-attribute' => true,
        ],
        function () {}
    );
});

Route::get('/instrument-nested', function () {
    Appsignal::instrument('parent', ['msg' => 'from parent span'], function () {
        $span = Appsignal::instrument('child', ['msg' => 'from child span']);
        $span->end();
    });
});

Route::get('/set-action', function () {
    Appsignal::setAction('my action');
});

Route::get('/custom-data', function () {
    Appsignal::addCustomData([
        'string-attribute' => 'abcdef',
        'int-attribute' => 1234,
        'bool-attribute' => true,
    ]);
});

Route::get('/tags', function () {
    Appsignal::addTags([
        'string-tag' => 'some value',
        'integer-tag' => 1234,
        'bool-tag' => true,
    ]);
});

Route::get('/log', function () {
    Log::info('My log');
});

Route::get('/log-with-attributes', function () {
    Log::info('My log with attributes', ['foo' => 'bar']);
});

Route::get('/set-gauge', function () {
    Appsignal::setGauge('my_gauge', 12);
    Appsignal::setGauge('my_gauge_with_attributes', 13, ["region" => "eu"]);
});

Route::get('/add-distribution-values', function () {
    Appsignal::addDistributionValue('memory_usage', 50);
    Appsignal::addDistributionValue('memory_usage', 70);

    Appsignal::addDistributionValue('with_attributes', 10, ["region" => "eu"]);
    Appsignal::addDistributionValue('with_attributes', 20, ["region" => "eu"]);
    Appsignal::addDistributionValue('with_attributes', 30, ["region" => "eu"]);
});

Route::get('/counter', function () {
    Appsignal::incrementCounter("my_counter", 1);
    Appsignal::incrementCounter("my_counter", 3, ["region" => "eu"]);
});

Route::get('/error', [ErrorsController::class, 'show']);
Route::get('/error-nested', [ErrorsController::class, 'nested']);
