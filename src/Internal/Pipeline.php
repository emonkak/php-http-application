<?php

namespace Emonkak\HttpMiddleware\Internal;

use Emonkak\HttpException\NotFoundHttpException;
use Interop\Http\Middleware\DelegateInterface;
use Psr\Http\Message\RequestInterface;

/**
 * @internal
 */
class Pipeline implements DelegateInterface
{
    /**
     * @var \SplQueue
     */
    private $middlewares;

    /**
     * @param \SplQueue $middlewares
     */
    public function __construct(\SplQueue $middlewares)
    {
        $this->middlewares = $middlewares;
    }

    /**
     * {@inheritDoc}
     */
    public function process(RequestInterface $request)
    {
        if ($this->middlewares->isEmpty()) {
            throw new NotFoundHttpException('No middleware available for processing');
        }

        $middleware = $this->middlewares->dequeue();

        return $middleware->process($request, $this);
    }
}
