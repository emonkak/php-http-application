<?php

namespace Emonkak\HttpMiddleware;

use Interop\Http\Middleware\DelegateInterface;
use Interop\Http\Middleware\ServerMiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;

class PredicateDecorator implements ServerMiddlewareInterface
{
    /**
     * @var ServerMiddlewareInterface
     */
    private $middleware;

    /**
     * @var callable
     */
    private $predicate;

    /**
     * @param ServerMiddlewareInterface $middleware
     * @param callable                  $predicate
     */
    public function __construct(ServerMiddlewareInterface $middleware, callable $predicate)
    {
        $this->middleware = $middleware;
        $this->predicate = $predicate;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $predicate = $this->predicate;
        if (!$predicate($request)) {
            return $delegate->process($request);
        }

        return $this->middleware->process($request, $delegate);
    }
}
