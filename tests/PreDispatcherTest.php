<?php

declare(strict_types=1);

namespace Emonkak\HttpMiddleware\Tests\Middleware;

use Emonkak\HttpMiddleware\PreDispatcher;
use Emonkak\Router\RouterInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @covers \Emonkak\HttpMiddleware\PreDispatcher
 */
class PreDispatcherTest extends TestCase
{
    /**
     * @expectedException \Emonkak\HttpException\NotFoundHttpException
     */
    public function testNotMatched()
    {
        $path = '/foo/123';

        $request = $this->createMockRequest('GET', $path);
        $response = $this->createMock(ResponseInterface::class);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler
            ->expects($this->never())
            ->method('handle')
            ->willReturn($response);

        $responseFactory = $this->createMock(ResponseFactoryInterface::class);
        $responseFactory
            ->expects($this->never())
            ->method('createResponse');

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->once())
            ->method('match')
            ->with($path)
            ->willReturn(null);

        $dispatcher = new PreDispatcher($responseFactory, $router);

        $dispatcher->process($request, $handler);
    }

    public function testHeadRequest()
    {
        $path = '/foo/123/bar/456';

        $request = $this->createMockRequest('HEAD', $path);
        $request
            ->expects($this->exactly(3))
            ->method('withAttribute')
            ->withConsecutive(
                ['foo_id', '123'],
                ['bar_id', '456'],
                ['__handler_reference', DispatcherTestMiddleware::class]
            )
            ->will($this->returnSelf());

        $response = $this->createMock(ResponseInterface::class);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler
            ->expects($this->once())
            ->method('handle')
            ->willReturn($response);

        $responseFactory = $this->createMock(ResponseFactoryInterface::class);
        $responseFactory
            ->expects($this->never())
            ->method('createResponse');

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->once())
            ->method('match')
            ->with($path)
            ->willReturn([
                ['HEAD' => DispatcherTestMiddleware::class],
                ['foo_id' => '123', 'bar_id' => '456'],
            ]);

        $dispatcher = new PreDispatcher($responseFactory, $router);

        $this->assertSame($response, $dispatcher->process($request, $handler));
    }

    public function testHeadRequestWithFallback()
    {
        $path = '/foo/123/bar/456';

        $request = $this->createMockRequest('HEAD', $path);
        $request
            ->expects($this->exactly(3))
            ->method('withAttribute')
            ->withConsecutive(
                ['foo_id', '123'],
                ['bar_id', '456'],
                ['__handler_reference', DispatcherTestMiddleware::class]
            )
            ->will($this->returnSelf());

        $response = $this->createMock(ResponseInterface::class);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler
            ->expects($this->once())
            ->method('handle')
            ->willReturn($response);

        $responseFactory = $this->createMock(ResponseFactoryInterface::class);
        $responseFactory
            ->expects($this->never())
            ->method('createResponse');

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->once())
            ->method('match')
            ->with($path)
            ->willReturn([
                ['GET' => DispatcherTestMiddleware::class],
                ['foo_id' => '123', 'bar_id' => '456'],
            ]);

        $dispatcher = new PreDispatcher($responseFactory, $router);

        $this->assertSame($response, $dispatcher->process($request, $handler));
    }

    public function testOptionsRequest()
    {
        $path = '/foo/123/bar/456';

        $request = $this->createMockRequest('OPTIONS', $path);

        $response = $this->createMock(ResponseInterface::class);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler
            ->expects($this->never())
            ->method('handle');

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->once())
            ->method('withHeader')
            ->with($this->identicalTo('Allow'), $this->identicalTo('GET, HEAD, OPTIONS'))
            ->will($this->returnSelf());

        $responseFactory = $this->createMock(ResponseFactoryInterface::class);
        $responseFactory
            ->expects($this->once())
            ->method('createResponse')
            ->with($this->identicalTo(204))
            ->willReturn($response);

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->once())
            ->method('match')
            ->with($path)
            ->willReturn([
                ['GET' => DispatcherTestMiddleware::class],
                ['foo_id' => '123', 'bar_id' => '456'],
            ]);

        $dispatcher = new PreDispatcher($responseFactory, $router);

        $this->assertSame($response, $dispatcher->process($request, $handler));
    }

    /**
     * @expectedException \Emonkak\HttpException\MethodNotAllowedHttpException
     */
    public function testMethodNotAllowed()
    {
        $path = '/foo/123/bar/456';

        $request = $this->createMockRequest('POST', $path);

        $response = $this->createMock(ResponseInterface::class);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler
            ->expects($this->never())
            ->method('handle');

        $responseFactory = $this->createMock(ResponseFactoryInterface::class);
        $responseFactory
            ->expects($this->never())
            ->method('createResponse');

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->once())
            ->method('match')
            ->with($path)
            ->willReturn([
                ['GET' => DispatcherTestMiddleware::class],
                ['foo_id' => 123, 'bar_id' => 456],
            ]);

        $dispatcher = new PreDispatcher($responseFactory, $router);

        $this->assertSame($response, $dispatcher->process($request, $handler));
    }

    private function createMockRequest($method, $path)
    {
        $uri = $this->createMock(UriInterface::class);
        $uri
            ->expects($this->any())
            ->method('getPath')
            ->willReturn($path);

        $request = $this->createMock(ServerRequestInterface::class);
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
