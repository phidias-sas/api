<?php
namespace Phidias\Api\Dispatcher;

use Psr\Http\Message\ServerRequestInterface as ServerRequest;

interface DispatcherInterface
{
    public function dispatch(ServerRequest $request);
    public function merge(DispatcherInterface $dispatcher);
    public function setArguments(array $arguments = []);
}