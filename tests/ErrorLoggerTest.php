<?php

namespace Emonkak\HttpMiddleware\Tests;

use Emonkak\HttpException\HttpExceptionInterface;
use Emonkak\HttpMiddleware\ErrorHandlerInterface;
use Emonkak\HttpMiddleware\ErrorLogger;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LogLevel;
use Psr\Log\LoggerInterface;

/**
 * @covers Emonkak\HttpMiddleware\ErrorLogger
 */
class ErrorLoggerTest extends TestCase
{
    /**
     * @dataProvider providerProcessError
     */
    public function testProcessError($statusCode, $expectedLogLevel)
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $exception = $this->createMock(HttpExceptionInterface::class);
        $exception
            ->expects($this->once())
            ->method('getStatusCode')
            ->willReturn($statusCode);

        $handler = $this->createMock(ErrorHandlerInterface::class);
        $handler
            ->expects($this->once())
            ->method('handleError')
            ->willReturn($response);

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('log')
            ->with(
                $expectedLogLevel,
                'Uncaught exception',
                ['exception' => $exception]
            );

        $errorLogger = new ErrorLogger($logger);

        $this->assertSame($response, $errorLogger->processError($request, $exception, $handler));
    }

    public function providerProcessError()
    {
        return [
            [301, LogLevel::INFO],
            [404, LogLevel::INFO],
            [400, LogLevel::WARNING],
            [500, LogLevel::ERROR],
        ];
    }
}
