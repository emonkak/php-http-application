<?php

namespace Emonkak\HttpMiddleware\Tests;

use Emonkak\HttpException\HttpExceptionInterface;
use Emonkak\HttpMiddleware\ErrorDelegateInterface;
use Emonkak\HttpMiddleware\ErrorLogger;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LogLevel;
use Psr\Log\LoggerInterface;

/**
 * @covers Emonkak\HttpMiddleware\ErrorLogger
 */
class ErrorLoggerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider providerHandleError
     */
    public function testHandleError($statusCode, $expectedLogLevel)
    {
        $request = $this->getMock(ServerRequestInterface::class);
        $response = $this->getMock(ResponseInterface::class);

        $exception = $this->getMock(HttpExceptionInterface::class);
        $exception
            ->expects($this->once())
            ->method('getStatusCode')
            ->willReturn($statusCode);

        $delegate = $this->getMock(ErrorDelegateInterface::class);
        $delegate
            ->expects($this->once())
            ->method('processError')
            ->willReturn($response);

        $logger = $this->getMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('log')
            ->with(
                $expectedLogLevel,
                'Uncaught exception',
                ['exception' => $exception]
            );

        $errorLogger = new ErrorLogger($logger);

        $this->assertSame($response, $errorLogger->processError($request, $exception, $delegate));
    }

    public function providerHandleError()
    {
        return [
            [301, LogLevel::INFO],
            [404, LogLevel::INFO],
            [400, LogLevel::WARNING],
            [500, LogLevel::ERROR],
        ];
    }
}
