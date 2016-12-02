<?php

namespace Emonkak\HttpMiddleware;

use Emonkak\HttpException\HttpExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface ErrorDelegateInterface
{
    /**
     * @param RequestInterface $request
     * @param HttpExceptionInterface $exception
     * @return ResponseInterface
     */
    public function processError(RequestInterface $request, HttpExceptionInterface $exception);
}
