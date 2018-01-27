<?php

namespace Emonkak\HttpMiddleware\Internal;

use Emonkak\HttpException\HttpExceptionInterface;
use Emonkak\HttpMiddleware\ErrorHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * @internal
 */
class ErrorPipeline implements ErrorHandlerInterface
{
    /**
     * @var \SplQueue
     */
    private $errorMiddlewares;

    /**
     * @param \SplQueue $errorMiddlewares
     */
    public function __construct(\SplQueue $errorMiddlewares)
    {
        $this->errorMiddlewares = $errorMiddlewares;
    }

    /**
     * @param RequestInterface       $request
     * @param HttpExceptionInterface $exception
     * @return ResponseInterface
     */
    public function handleError(ServerRequestInterface $request, HttpExceptionInterface $exception): ResponseInterface
    {
        if ($this->errorMiddlewares->isEmpty()) {
            throw $exception;
        }

        $errorMiddleware = $this->errorMiddlewares->dequeue();

        return $errorMiddleware->processError($request, $exception, $this);
    }
}
