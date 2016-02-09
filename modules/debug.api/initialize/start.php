<?php

use Phidias\Utilities\Debugger;
use Phidias\Api\Environment;

$request = Environment::getServerRequest();
$debugId = $request->getHeaderLine("X-Phidias-Debug");

if ($debugId) {

    Debugger::enable();

    register_shutdown_function(function() use ($debugId) {

        $tmpFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . md5($debugId) . '.debug.json';
        file_put_contents($tmpFile, Debugger::toJson());

    });

}