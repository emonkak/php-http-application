<?php

namespace Emonkak\HttpMiddleware\Tests;

use Emonkak\HttpException\HttpException;
use Emonkak\HttpException\InternalServerErrorHttpException;
use Emonkak\HttpMiddleware\Application;
use Emonkak\HttpMiddleware\ErrorMiddlewareInterface;
use Emonkak\HttpMiddleware\Internal\ErrorPipeline;
use Emonkak\HttpMiddleware\Internal\Pipeline;
use Interop\Http\Middleware\ServerMiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

/**
 * @covers Emonkak\HttpMiddleware\Application
 */
class ApplicationTest extends \PHPUnit_Framework_TestCase
{
    public function testPipe()
    {
        $request = $this->getMock(ServerRequestInterface::class);
        $response = $this->getMock(ResponseInterface::class);

        $middleware = $this->getMock(ServerMiddlewareInterface::class);
        $middleware
            ->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($request), $this->isInstanceOf(Pipeline::class))
            ->willReturn($response);

        $app = new Application();
        $app
            ->pipe($middleware);

        $this->assertSame($response, $app->handle($request));
    }

    public function testPipeIf()
    {
        $request = $this->getMock(ServerRequestInterface::class);
        $response = $this->getMock(ResponseInterface::class);

        $falseMiddleware = $this->getMock(ServerMiddlewareInterface::class);
        $falseMiddleware
            ->expects($this->never())
            ->method('process');

        $trueMiddleware = $this->getMock(ServerMiddlewareInterface::class);
        $trueMiddleware
            ->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($request), $this->isInstanceOf(Pipeline::class))
            ->willReturn($response);

        $app = new Application();
        $app
            ->pipeIf($falseMiddleware, function(ServerRequestInterface $request) {
                return false;
            })
            ->pipeIf($trueMiddleware, function(ServerRequestInterface $request) {
                return true;
            });

        $this->assertSame($response, $app->handle($request));
    }

    public function testPipeOn()
    {
        $request = $this->createMockRequest('GET', '/bar/123');
        $response = $this->getMock(ResponseInterface::class);

        $fooMiddleware = $this->getMock(ServerMiddlewareInterface::class);
        $fooMiddleware
            ->expects($this->never())
            ->method('process');

        $barMiddleware = $this->getMock(ServerMiddlewareInterface::class);
        $barMiddleware
            ->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($request), $this->isInstanceOf(Pipeline::class))
            ->willReturn($response);

        $app = new Application();
        $app
            ->pipeOn($fooMiddleware, '/foo/')
            ->pipeOn($barMiddleware, '/bar/');

        $this->assertSame($response, $app->handle($request));
    }

    public function testPipeOnHttpException()
    {
        $request = $this->createMockRequest('GET', '/bar/123');
        $response = $this->getMock(ResponseInterface::class);

        $exception = new HttpException(500);

        $middleware = $this->getMock(ServerMiddlewareInterface::class);
        $middleware
            ->expects($this->once())
            ->method('process')
            ->with(
                $this->identicalTo($request),
                $this->isInstanceOf(Pipeline::class)
            )
            ->will($this->throwException($exception));

        $errorMiddleware = $this->getMock(ErrorMiddlewareInterface::class);
        $errorMiddleware
            ->expects($this->once())
            ->method('processError')
            ->with(
                $this->identicalTo($request),
                $this->identicalTo($exception),
                $this->isInstanceOf(ErrorPipeline::class)
            )
            ->willReturn($response);

        $app = new Application();
        $app
            ->pipe($middleware)
            ->pipeOnError($errorMiddleware);

        $this->assertSame($response, $app->handle($request));
    }

    public function testPipeOnGenericException()
    {
        $request = $this->createMockRequest('GET', '/bar/123');
        $response = $this->getMock(ResponseInterface::class);

        $exception = new \Exception();

        $middleware = $this->getMock(ServerMiddlewareInterface::class);
        $middleware
            ->expects($this->once())
            ->method('process')
            ->with(
                $this->identicalTo($request),
                $this->isInstanceOf(Pipeline::class)
            )
            ->will($this->throwException($exception));

        $errorMiddleware = $this->getMock(ErrorMiddlewareInterface::class);
        $errorMiddleware
            ->expects($this->once())
            ->method('processError')
            ->with(
                $this->identicalTo($request),
                $this->isInstanceOf(InternalServerErrorHttpException::class),
                $this->isInstanceOf(ErrorPipeline::class)
            )
            ->willReturn($response);

        $app = new Application();
        $app
            ->pipe($middleware)
            ->pipeOnError($errorMiddleware);

        $this->assertSame($response, $app->handle($request));
    }

    private function createMockRequest($method, $path)
    {
        $uri = $this->getMock(UriInterface::class);
        $uri
            ->expects($this->any())
            ->method('getPath')
            ->willReturn($path);

        $request = $this->getMock(ServerRequestInterface::class);
        $request
            ->expects($this->any())
            ->method('getMethod')
            ->willReturn($method);
        $request
            ->expects($this->any())
            ->method('getUri')
            ->willReturn($uri);

        return $request;
    }
}
