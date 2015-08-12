<?php namespace Phidias\Server\Response\ClientError;

/**
 * 406 Not Acceptable
 * The resource identified by the request is only capable of generating response entities which have content characteristics not acceptable according to the accept headers sent in the request.
 *
 */
class NotAcceptable extends \Phidias\Server\Response\ClientError
{
    public $code = 406; //HTTP 406: Not Acceptable
}
