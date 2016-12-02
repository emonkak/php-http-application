<?php

namespace Emonkak\HttpMiddleware\Tests\Internal;

use Emonkak\HttpException\HttpException;
use Emonkak\HttpException\HttpExceptionInterface;
use Emonkak\HttpMiddleware\ErrorMiddlewareInterface;
use Emonkak\HttpMiddleware\Internal\ErrorPipeline;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @covers Emonkak\HttpMiddleware\Internal\ErrorPipeline
 */
class ErrorPipelineTest extends \PHPUnit_Framework_TestCase
{
    public function testHandleError()
    {
        $request = $this->getMock(ServerRequestInterface::class);
        $exception = $this->getMock(HttpExceptionInterface::class);
        $response = $this->getMock(ResponseInterface::class);

        $errorMiddlewares = [
            $this->getMock(ErrorMiddlewareInterface::class),
            $this->getMock(ErrorMiddlewareInterface::class),
            $this->getMock(ErrorMiddlewareInterface::class),
        ];

        $queue = new \SplQueue();
        $errorPipeline = new ErrorPipeline($queue);
        $delegate = function($request, $exception, $delegate) {
            return $delegate->processError($request, $exception);
        };

        $errorMiddlewares[0]
            ->expects($this->once())
            ->method('processError')
            ->with(
                $this->identicalTo($request),
                $this->identicalTo($exception),
                $this->identicalTo($errorPipeline)
            )
            ->will($this->returnCallback($delegate));
        $errorMiddlewares[1]
            ->expects($this->once())
            ->method('processError')
            ->with(
                $this->identicalTo($request),
                $this->identicalTo($exception),
                $this->identicalTo($errorPipeline)
            )
            ->will($this->returnCallback($delegate));
        $errorMiddlewares[2]
            ->expects($this->once())
            ->method('processError')
            ->with(
                $this->identicalTo($request),
                $this->identicalTo($exception),
                $this->identicalTo($errorPipeline)
            )
            ->willReturn($response);

        foreach ($errorMiddlewares as $errorMiddleware) {
            $queue->enqueue($errorMiddleware);
        }

        $this->assertSame($response, $errorPipeline->processError($request, $exception));
    }

    /**
     * @expectedException Emonkak\HttpException\HttpExceptionInterface
     */
    public function testHandleErrorThrowsHttpExceptionInterface()
    {
        $request = $this->getMock(ServerRequestInterface::class);
        $exception = new HttpException(500);

        $queue = new \SplQueue();
        $errorPipeline = new ErrorPipeline($queue);

        $errorPipeline->processError($request, $exception);
    }
}
