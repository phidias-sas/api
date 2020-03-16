<?php

return [
    "oauth/authorization" => [
        "post" => [
            "validation" => function($input) {
                //See http://tools.ietf.org/html/rfc6749#section-4.1.1
                $requiredKeys = [
                    "response_type",
                    "client_id",
                    "redirect_uri",
                    "scope",
                    "state"
                ];

                $errors = [];

                foreach ($requiredKeys as $key) {
                    if (!isset($input->$key)) {
                        $errors[$key] = "$key is required";
                    }
                }

                return $errors;
            },

            "controller" => "Phidias\Api\Oauth\Controller->authorization({request})",

            "catch" => [
                "Exception" => function($request, $response) {
                    $response->status(422);
                }
            ]
        ]
    ]
];
