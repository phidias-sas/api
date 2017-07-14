<?php
$templateFolder = realpath(__DIR__."/../templates");

return [

    "/documentation" => [
        "get" => [
            "controller" => "Phidias\Documentation\Controller->main()",
            "interpreter" => [
               "text/html" => "$templateFolder/html/index.php"
            ]
        ]
    ]

];