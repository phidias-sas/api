<?php

return [
    "oauth/token" => [
        "post" => [
            "validation" => function($input) {
                if (!isset($input->grant_type)) {
                    return "no grant_type specified";
                }
            },

            "controller" => "Phidias\Api\Oauth\Controller->token({request}, {input})",

            "handler" => [
                "Exception" => function($request, $response, $exception) {
                    $response->status(422);
                    return $exception->getMessage();
                }
            ]
        ]
    ]
];
