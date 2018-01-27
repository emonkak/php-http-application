<?php

namespace Emonkak\HttpMiddleware;

use Emonkak\HttpException\HttpExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LogLevel;
use Psr\Log\LoggerInterface;

class ErrorLogger implements ErrorMiddlewareInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritDoc}
     */
    public function processError(ServerRequestInterface $request, HttpExceptionInterface $exception, ErrorHandlerInterface $handler): ResponseInterface
    {
        $this->logger->log(
            $this->getLogLevel($exception),
            'Uncaught exception',
            ['exception' => $exception]
        );
        return $handler->handleError($request, $exception);
    }

    /**
     * @param HttpExceptionInterface $exception
     * @return string
     */
    protected function getLogLevel(HttpExceptionInterface $exception): string
    {
        $statusCode = $exception->getStatusCode();
        if ($statusCode >= 500) {
            return LogLevel::ERROR;
        } elseif ($statusCode >= 400 && $statusCode !== 404) {
            return LogLevel::WARNING;
        } else {
            return LogLevel::INFO;
        }
    }
}
