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
     * @var ErrorMiddlewareInterface[]
     */
    private $errorMiddlewares;

    /**
     * @var int
     */
    private $index = 0;

    /**
     * @param ErrorMiddlewareInterface[] $errorMiddlewares
     */
    public function __construct(array $errorMiddlewares)
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
        if ($this->index >= count($this->errorMiddlewares)) {
            throw $exception;
        }

        $errorMiddleware = $this->errorMiddlewares[$this->index++];

        return $errorMiddleware->processError($request, $exception, $this);
    }
}
