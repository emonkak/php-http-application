<?php

declare(strict_types=1);

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

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $pipeline = new Pipeline($this->middlewares);
        return $pipeline->handle($request);
    }

    /**
     * @return $this
     */
    public function pipe(MiddlewareInterface $middleware): Application
    {
        $this->middlewares[] = $middleware;
        return $this;
    }
}
