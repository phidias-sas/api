<?php
namespace Phidias\Api\Dispatcher;

class Presets
{
    public static function templateByType()
    {
        $templatesPath = realpath(__DIR__."/../../templates/");

        return [

            "abstract" => true,

            "any" => [

                "template" => [

                    "application/json" => [
                        "Phidias\Db\Orm\Collection" => "$templatesPath/json/phidias/db/orm/collection.php",
                        "Phidias\Db\Orm\Entity"     => "$templatesPath/json/phidias/db/orm/entity.php"
                    ],

                    "text/html" => [
                        "Phidias\Db\Orm\Collection" => "$templatesPath/html/phidias/db/orm/collection.php",
                        "Phidias\Db\Orm\Entity"     => "$templatesPath/html/phidias/db/orm/entity.php"
                    ]


                ]

            ]

        ];

    }
}