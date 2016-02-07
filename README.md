# Phidias API
##### Available via composer
`composer require phidias/api`

#### Overview

##### Declare resources with a simple syntax

```
use Phidias\Api\Server;

Server::resource("/")
    ->on("get", function() {
        return "hello world";
    });


Server::run();
```


##### Keep your application logic separate

```
class Book
{
    public $title;
    public $review;

    public function __construct()
    {
        $rand = rand(1, 10);

        $this->title  = "$rand things I hate about you";
        $this->review = "I just wasted $rand hours";
    }
}

class BookManager
{
    public function getBookList()
    {
        // ... do your voodoo
        $books = [new Book, new Book, new Book];

        return $books;
    }
}



Server::resource("/books")
    ->on("get", "BookManager->getBookList()");


Server::run();

```


##### Route URL Attributes

```
Server::resource("/books/{bookId}")
    ->on("get", "BookManager->getBook({bookId})");
```


##### Handle multiple response formats

```
Server::resource("/books/{bookId}")
    ->on("get", Server::action()
        ->controller("BookManager->getBook({bookId})")

        ->render("text/html",       "BookManager->toHtml({output})")
        ->render("application/xml", "BookManager->toXml({output})")
    );

```

##### An example showcasing most features

```
<?php

Server::resource("/artices/{category}/{articleId}")

    ->on("get", Server::action()

        /*
        */
        ->accessControl(Server::access()
            ->allowCredentials(true)
            ->allowHeaders("X-Your-Header")
            ->exposeHeaders(["X-Mine-One", "X-Mine-Two"])
        )

        ->accessControl("full")

        /*
        */
        ->parse("text/xml", "Parser->readXML({input})")

        /*
        */
        ->authentication("ArticleFoo->voodoo({category}, {articleId}, {request}, {response})")

        /*
        */
        ->authorization("ArticleFoo->voodoo({category}, {articleId}, {request}, {response})")

        /*
        */
        ->validation("ArticleFoo->voodoo({category}, {articleId}, {request}, {response})")

        /*
        Controllers may be strings representing a function call.
        You can use URL attributes as arguments in this representation, and also
        use the the following predefined arguments:
        */
        ->controller("ArticleFoo->voodoo({category}, {articleId}, {request}, {response})")

        /*
        */
        ->filter("ArticleFoo->voodoo({category}, {articleId}, {request}, {response})")

        /*
        */
        ->render("text/xml",  "toXML({output})")
        ->render("text/html", "toHTML({output})")

        /*
        */
        ->handle("\Exception\ClassName",  "doSomething({exception})")

    );






/*
Running the server:

You can use it with PSR-7 messages:
*/

//Server::execute() given a Psr7 ServerRequest, returns a Psr7 response
$response = Server::execute($request);

/* or handle everything */
Server::run();

```