<?php

namespace Emonkak\HttpMiddleware\Internal;

use Emonkak\HttpException\NotFoundHttpException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @internal
 */
class Pipeline implements RequestHandlerInterface
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
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->middlewares->isEmpty()) {
            throw new NotFoundHttpException('No middleware available for processing');
        }

        $middleware = $this->middlewares->dequeue();

        return $middleware->process($request, $this);
    }
}
