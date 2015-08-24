# Phidias API
##### Available via composer
`composer require phidias/api`

#### Overview

##### Declare resources with a simple syntax

```
use Phidias\Api\Server;

Server::resource("/", [
    "get" => [
        "controller" => function() {
            return "Hello world";
        }
    ]
]);

Server::run();
```


##### Keep your application logic separate

```
class Book
{
    public $title;
    public $synopsis;
}

class BookManager
{
    public function getBookList()
    {
        // ... voodoo
        return $books;
    }
}
```
```
Server::resource("/books", [
    "get" => [
        "controller" => "BookManager->getBookList()"
    ]
]);
```


##### Route URL Attributes

```
Server::resource("/books/{bookId}", [
    "get" => [
        "controller" => "BookManager->getBook({bookId})"
    ]
]);
```


##### Handle multiple response formats

```
Server::resource("/books/{bookId}", [
    "get" => [
        "controller" => "BookManager->getBook({bookId})",
        "template" => [
            "text/html"        => "path/to/my/template/book.html.php",
            "application/json" => "path/to/my/template/book.json.php",
            // <mimetype> => <template file>
        ]
    ]
]);
```

path/to/my/template/book.html.php
```
<?php
/*
By default, templates are .php files with pre-defined global variables:
$data: The data returned by the controller
$request : The current HTTP Request (PSR-7 ServerRequest)
$response : The current HTTP Response (PSR-7 Response)

But you can easily plug in your favorite template engine.
*/
$book = $data;
?>
<div class="book">
    <h1><?= $book->title ?></h1>
    <p><?= $book->title ?><p>
</div>
```



##### An example showcasing most features

```
<?php

Server::resource("/artices/{category}/{articleId}", [


    /* 
    Methods 
    "any" specifies a dispatcher for any and all methods
    */

    "get" => [

        /*
        Controllers may be strings representing a function call.
        You can use URL attributes as arguments in this representation, and also
        use the the following predefined attributes:
        {request}
        {request.data} // The request body parsed as a PHP object
        {response}
        */
        "controller" => "ArticleFoo->voodoo({category}, {articleId}, {request}, {response})",

        /*
        Templates are PHP files
        */
        "template" => [
            "application/json" => "path/to/my/template/book.json.php",
            // <mimetype> => <template file>
        ],

        /*
        But you can plug in a third party template engine
        (you might have to write a wrapper class extending Phidias\Api\Dispatcher\TemplateEngine)
        */
        "templateEngine" => "myEngine",

        /*
        Authentication

        If authentication credentials are present, validate them and generate a token
        If a token is present, validate it
        If no token and no credentials are present, identify as guest

        Helpers:
        "authentication" => "Phidias\Api\Authentication\Oauth"

        */
        "authentication" => function($category, $articleId, $request, $response) {
        },

        /*
        Authorization
        */
        "authorization" => function($category, $articleId, $request, $response) {
            if ($category == "secret") {
                return false;
            }
        },

        /*
        Validation
        If the given [callback] throws an exception or returns false, a
        Dispatcher\Exception\ValidationException is thrown (HTTP 422 Unprocessable Entity)

        Helpers:
        "validation" => [
            "schema" => "/path/to/json/schema.json"
        ]

        */
        "validation" => function($category, $articleId, $request, $response) {
            // everything is valid
            return;
        }
    ]
]);





/*
Running the server:

You can use it with PSR-7 messages:
*/

//Server::execute() given a Psr7 ServerRequest, returns a Psr7 response
$response = Server::execute($request);


/* or handle everything */
Server::run();

```