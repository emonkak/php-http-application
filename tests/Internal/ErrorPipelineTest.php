<?php

namespace Emonkak\HttpMiddleware\Tests\Internal;

use Emonkak\HttpException\HttpException;
use Emonkak\HttpException\HttpExceptionInterface;
use Emonkak\HttpMiddleware\ErrorMiddlewareInterface;
use Emonkak\HttpMiddleware\Internal\ErrorPipeline;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @covers Emonkak\HttpMiddleware\Internal\ErrorPipeline
 */
class ErrorPipelineTest extends TestCase
{
    public function testHandleError()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $exception = $this->createMock(HttpExceptionInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $errorMiddlewares = [
            $this->createMock(ErrorMiddlewareInterface::class),
            $this->createMock(ErrorMiddlewareInterface::class),
            $this->createMock(ErrorMiddlewareInterface::class),
        ];

        $errorPipeline = new ErrorPipeline($errorMiddlewares);
        $handler = function($request, $exception, $handler) {
            return $handler->handleError($request, $exception);
        };

        $errorMiddlewares[0]
            ->expects($this->once())
            ->method('processError')
            ->with(
                $this->identicalTo($request),
                $this->identicalTo($exception),
                $this->identicalTo($errorPipeline)
            )
            ->will($this->returnCallback($handler));
        $errorMiddlewares[1]
            ->expects($this->once())
            ->method('processError')
            ->with(
                $this->identicalTo($request),
                $this->identicalTo($exception),
                $this->identicalTo($errorPipeline)
            )
            ->will($this->returnCallback($handler));
        $errorMiddlewares[2]
            ->expects($this->once())
            ->method('processError')
            ->with(
                $this->identicalTo($request),
                $this->identicalTo($exception),
                $this->identicalTo($errorPipeline)
            )
            ->willReturn($response);

        $this->assertSame($response, $errorPipeline->handleError($request, $exception));
    }

    /**
     * @expectedException Emonkak\HttpException\HttpExceptionInterface
     */
    public function testHandleErrorThrowsHttpExceptionInterface()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $exception = new HttpException(500);

        $errorPipeline = new ErrorPipeline([]);

        $errorPipeline->handleError($request, $exception);
    }
}
