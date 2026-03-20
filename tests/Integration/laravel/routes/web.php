<?php

use App\Http\Controllers\ErrorsController;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use AppSignal\AppSignal;

Route::get('/', function () {});

Route::post('/', function () {});

Route::get('/instrument', function () {
    AppSignal::instrument(
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
    AppSignal::instrument('parent', ['msg' => 'from parent span'], function () {
        $span = AppSignal::instrument('child', ['msg' => 'from child span']);
        $span->end();
    });
});

Route::get('/set-action', function () {
    AppSignal::setAction('my action');
});

Route::get('/custom-data', function () {
    AppSignal::addCustomData([
        'string-attribute' => 'abcdef',
        'int-attribute' => 1234,
        'bool-attribute' => true,
    ]);
});

Route::get('/tags', function () {
    AppSignal::addTags([
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
    AppSignal::setGauge('my_gauge', 12);
    AppSignal::setGauge('my_gauge_with_attributes', 13, ["region" => "eu"]);
});

Route::get('/add-distribution-values', function () {
    AppSignal::addDistributionValue('memory_usage', 50);
    AppSignal::addDistributionValue('memory_usage', 70);

    AppSignal::addDistributionValue('with_attributes', 10, ["region" => "eu"]);
    AppSignal::addDistributionValue('with_attributes', 20, ["region" => "eu"]);
    AppSignal::addDistributionValue('with_attributes', 30, ["region" => "eu"]);
});

Route::get('/counter', function () {
    AppSignal::incrementCounter("my_counter", 1);
    AppSignal::incrementCounter("my_counter", 3, ["region" => "eu"]);
});

Route::get('/error', [ErrorsController::class, 'show']);
Route::get('/error-nested', [ErrorsController::class, 'nested']);
