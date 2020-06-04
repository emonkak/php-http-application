<?php

declare(strict_types=1);

namespace Emonkak\HttpApplication;

use Emonkak\HttpException\HttpExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class ErrorLogger implements MiddlewareInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (HttpExceptionInterface $e) {
            $this->logger->log(
                $this->getLogLevel($e),
                'Uncaught ' . get_class($e),
                ['exception' => $e]
            );
            throw $e;
        } catch (\Throwable $e) {
            $this->logger->log(
                $this->getDefaultLogLevel(),
                'Uncaught ' . get_class($e),
                ['exception' => $e]
            );
            throw $e;
        }
    }

    protected function getLogLevel(HttpExceptionInterface $exception): string
    {
        $statusCode = $exception->getStatusCode();
        if ($statusCode >= 500) {
            return LogLevel::ERROR;
        } elseif ($statusCode >= 400 && $statusCode !== 404 && $statusCode !== 405) {
            return LogLevel::WARNING;
        } else {
            return LogLevel::DEBUG;
        }
    }

    protected function getDefaultLogLevel(): string
    {
        return LogLevel::ERROR;
    }
}
