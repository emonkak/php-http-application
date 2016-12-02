<?php

namespace Emonkak\HttpMiddleware;

use Emonkak\HttpException\HttpExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface ErrorMiddlewareInterface
{
    /**
     * @param ServerRequestInterface $request
     * @param HttpExceptionInterface $exception
     * @param ErrorDelegateInterface $next
     * @return ResponseInterface
     */
    public function processError(ServerRequestInterface $request, HttpExceptionInterface $exception, ErrorDelegateInterface $delegate);
}
