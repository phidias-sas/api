<?php 
use Phidias\Api\Http\Stream;

return [

    "/debug" => [
        "get" => [
            "controller" => function() {},
            "render" => [
                "text/html" => realpath(__DIR__.'/../app/index.html')
            ]
        ]
    ],

    "/debug/{debugId}" => [

        "get" => [

            "validation" => function($debugId, $response) {

                $tmpFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . md5($debugId) . '.debug.json';

                if (!is_file($tmpFile)) {
                    $response->status(404);
                    return "No debug data available for '$debugId'. Generate data here by including the request header 'X-Phidias-Debug: $debugId'";
                }

            },

            "controller" => function($debugId, $response) {

                $tmpFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . md5($debugId) . '.debug.json';
                
                return $response
                    ->withHeader("Last-Modified", gmdate('D, d M Y H:i:s T', filemtime($tmpFile)))
                    ->withHeader("Content-Type", "application/json")
                    ->withBody(new Stream($tmpFile));

            }
        ]
    ]

];