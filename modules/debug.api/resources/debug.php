<?php
use Phidias\Api\Http\Stream;


function getRandomDebugId()
{
    $syll = ["ma", "bu", "la", "pi", "ba", "ni", "ru", "ga", "fa"];
    $max  = count($syll)-1;

    return $syll[rand(0, $max)] . $syll[rand(0, $max)] . $syll[rand(0, $max)];
}


return [

    "/debug" => [
        "get" => [
            "controller" => function($request, $response) {

                $newDebugId = getRandomDebugId();

                // !!! 
                // This only works if the current
                // index.php is NOT being served as a subfolder
                $location = "/debug/{$newDebugId}";

                // i.e. if index.php is in
                // localhost/subfolder/apps/index.php
                // then this location would be interpreted as localhost/debug/{newDebugId}
                
                // So it must be prefixed with the whole 
                // host and path, but Phidias set the $request
                // path as /debug/,  not /subfolder/debug/
                // anyway, think of a permanent solution

                $uri = $request->getUri();

                $scheme     = $uri->getScheme();
                $host       = $uri->getHost();
                
                $uriPath    = $uri->getPath();                            //  /debug/
                $actualPath = filter_input(INPUT_SERVER, "REQUEST_URI");  //  /subfolder/debug/

                $path = trim($actualPath, '/');

                $location = "$scheme://$host/$path/$newDebugId/";

                return $response->withStatus(303, "See other")
                    ->withHeader("Location", $location);

            }
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

                $tmpFile  = sys_get_temp_dir() . DIRECTORY_SEPARATOR . md5($debugId) . '.debug.json';

                $response->header("Last-Modified", gmdate('D, d M Y H:i:s T', filemtime($tmpFile)));

                return json_decode(file_get_contents($tmpFile), JSON_PRETTY_PRINT);

            },

            "interpreter" => [
                "text/html" => realpath(__DIR__.'/../app/index.html')
            ]
        ]
    ]

];