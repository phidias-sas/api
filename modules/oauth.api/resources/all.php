<?php
return [
    "*" => [
        "abstract" => true,
        "any" => [
            "authentication" => "Phidias\Api\Oauth\Controller::authenticate({request})"
        ]
    ]
];
