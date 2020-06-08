<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

// ================== UPLOAD FILE TO S3

$router->get('upload_file', ['uses' => 'FileController@index']);

$router->post('upload', ['uses' => 'FileController@process','middleware' => 'auth_f6']); // Qua App

$router->post('process', ['uses' => 'FileController@process']); // Qua Web
$router->options('process', ['uses' => 'FileController@process']);


$router->delete('revert', ['uses' => 'FileController@revert']);
$router->options('revert', [ 'uses' => 'FileController@process']);

$router->post('formalization', ['uses' => 'FileController@formalization']);




