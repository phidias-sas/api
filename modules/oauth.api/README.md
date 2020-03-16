## Phidias OAuth endpoints

### Setup
Import OAuth endpoints in your server

```
Server::import("vendor/phidias/api/modules/oauth.api");
```


### Configuration

Define a credential validation function that takes
username and password and return payload data.

Return false or throw exceptions when authentication fails

```
Phidias\Api\Oauth\Controller::addCredentialsValidator(function ($username, $password) {
    // do your voodoo
    return $payload;
});

```


### Access the token payload globally from you application

```
use Phidias\Api\Oauth\Token;

$payload = Token::getPayload();

if ($payload === null) {
    die("you are not authenticated");
}

echo "Welcome back, {$payload->firstName}!";

```