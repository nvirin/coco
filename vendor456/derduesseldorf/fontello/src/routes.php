<?php

$namespace = 'Derduesseldorf\Fontello\Controllers\\';

Route::get('fontelloimport', ['uses' => $namespace.'FontelloImportController@getIndex', 'as' => 'fontello.start.import']);
Route::get('fontellocallback', ['uses' => $namespace.'FontelloImportController@getCallback', 'as' => 'fontello.callback.import']);
Route::post('fontellorunimport', ['uses' => $namespace.'FontelloImportController@getRunImport', 'as' => 'fontello.run.import']);