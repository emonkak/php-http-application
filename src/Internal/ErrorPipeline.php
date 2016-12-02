<?php

namespace Emonkak\HttpMiddleware\Internal;

use Emonkak\HttpException\HttpExceptionInterface;
use Emonkak\HttpMiddleware\ErrorDelegateInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * @internal
 */
class ErrorPipeline implements ErrorDelegateInterface
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
    public function processError(RequestInterface $request, HttpExceptionInterface $exception)
    {
        if ($this->errorMiddlewares->isEmpty()) {
            throw $exception;
        }

        $errorMiddleware = $this->errorMiddlewares->dequeue();

        return $errorMiddleware->processError($request, $exception, $this);
    }
}
