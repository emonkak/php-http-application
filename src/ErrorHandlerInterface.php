<?php

namespace Emonkak\HttpMiddleware;

use Emonkak\HttpException\HttpExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

interface ErrorHandlerInterface
{
    /**
     * @param ServerRequestInterface $request
     * @param HttpExceptionInterface $exception
     * @return ResponseInterface
     */
    public function handleError(ServerRequestInterface $request, HttpExceptionInterface $exception): ResponseInterface;
}
