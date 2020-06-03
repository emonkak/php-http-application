<?php

declare(strict_types=1);

namespace Emonkak\HttpMiddleware\Tests;

use Emonkak\HttpException\HttpException;
use Emonkak\HttpMiddleware\ErrorLogger;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * @covers \Emonkak\HttpMiddleware\ErrorLogger
 */
class ErrorLoggerTest extends TestCase
{
    /**
     * @dataProvider providerHttpExeption
     */
    public function testHttpExeption($statusCode, $expectedLogLevel)
    {
        $request = $this->createMock(ServerRequestInterface::class);

        $exception = new HttpException($statusCode);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler
            ->expects($this->once())
            ->method('handle')
            ->with($this->identicalTo($request))
            ->will($this->throwException($exception));

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('log')
            ->with(
                $expectedLogLevel,
                'Uncaught ' . get_class($exception),
                ['exception' => $exception]
            );

        $errorLogger = new ErrorLogger($logger);

        try {
            $errorLogger->process($request, $handler);
            $this->assertFail();
        } catch (HttpException $e) {
            if ($exception !== $e) {
                throw $e;
            }
        }
    }

    public function providerHttpExeption()
    {
        return [
            [301, LogLevel::DEBUG],
            [404, LogLevel::DEBUG],
            [405, LogLevel::DEBUG],
            [400, LogLevel::WARNING],
            [500, LogLevel::ERROR],
        ];
    }

    public function testException()
    {
        $request = $this->createMock(ServerRequestInterface::class);

        $exception = new \Exception();

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler
            ->expects($this->once())
            ->method('handle')
            ->with($this->identicalTo($request))
            ->will($this->throwException($exception));

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('log')
            ->with(
                LogLevel::ERROR,
                'Uncaught ' . get_class($exception),
                ['exception' => $exception]
            );

        $errorLogger = new ErrorLogger($logger);

        try {
            $errorLogger->process($request, $handler);
            $this->assertFail();
        } catch (\Exception $e) {
            if ($exception !== $e) {
                throw $e;
            }
        }
    }
}
