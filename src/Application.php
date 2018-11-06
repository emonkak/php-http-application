<?php

namespace Emonkak\HttpMiddleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Application implements RequestHandlerInterface
{
    /**
     * @var MiddlewareInterface[]
     */
    private $middlewares = [];

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $pipeline = new Pipeline($this->middlewares);
        return $pipeline->handle($request);
    }

    /**
     * @param MiddlewareInterface $middleware
     * @return $this
     */
    public function pipe(MiddlewareInterface $middleware): Application
    {
        $this->middlewares[] = $middleware;
        return $this;
    }
}
