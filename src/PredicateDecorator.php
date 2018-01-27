<?php

namespace Emonkak\HttpMiddleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class PredicateDecorator implements MiddlewareInterface
{
    /**
     * @var MiddlewareInterface
     */
    private $middleware;

    /**
     * @var callable
     */
    private $predicate;

    /**
     * @param MiddlewareInterface $middleware
     * @param callable                  $predicate
     */
    public function __construct(MiddlewareInterface $middleware, callable $predicate)
    {
        $this->middleware = $middleware;
        $this->predicate = $predicate;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $predicate = $this->predicate;
        if (!$predicate($request)) {
            return $handler->handle($request);
        }

        return $this->middleware->process($request, $handler);
    }
}
