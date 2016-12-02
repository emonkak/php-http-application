<?php

namespace Emonkak\HttpMiddleware\Tests\Middleware;

use Emonkak\HttpMiddleware\Dispatcher;
use Emonkak\Router\RouterInterface;
use Interop\Container\ContainerInterface;
use Interop\Http\Middleware\DelegateInterface;
use Interop\Http\Middleware\ServerMiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

/**
 * @covers Emonkak\HttpMiddleware\Dispatcher
 */
class DispatcherTest extends \PHPUnit_Framework_TestCase
{
    public function testNoMatch()
    {
        $path = '/foo/123';

        $request = $this->createMockRequest('GET', $path);
        $response = $this->getMock(ResponseInterface::class);

        $delegate = $this->getMock(DelegateInterface::class);
        $delegate
            ->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($request))
            ->willReturn($response);

        $router = $this->getMock(RouterInterface::class);
        $router
            ->expects($this->once())
            ->method('match')
            ->with($path)
            ->willReturn(null);

        $container = $this->getMock(ContainerInterface::class);

        $dispatcher = new Dispatcher($router, $container);

        $this->assertSame($response, $dispatcher->process($request, $delegate));
    }

    public function testMatchedController()
    {
        $path = '/foo/123/bar/456';

        $request = $this->createMockRequest('GET', $path);
        $request
            ->expects($this->exactly(2))
            ->method('withAttribute')
            ->withConsecutive(
                ['foo_id', 123],
                ['bar_id', 456]
            )
            ->will($this->returnSelf());

        $response = $this->getMock(ResponseInterface::class);

        $delegate = $this->getMock(DelegateInterface::class);
        $delegate
            ->expects($this->never())
            ->method('process');

        $router = $this->getMock(RouterInterface::class);
        $router
            ->expects($this->once())
            ->method('match')
            ->with($path)
            ->willReturn([
                ['GET' => [DispatcherTestController::class, 'show']],
                ['foo_id' => 123, 'bar_id' => 456]
            ]);

        $container = $this->getMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('get')
            ->with(DispatcherTestController::class)
            ->willReturn(new DispatcherTestController($response));

        $dispatcher = new Dispatcher($router, $container);

        $this->assertSame($response, $dispatcher->process($request, $delegate));
    }

    public function testMatchedMiddleware()
    {
        $path = '/foo/123/bar/456';

        $request = $this->createMockRequest('GET', $path);
        $request
            ->expects($this->exactly(2))
            ->method('withAttribute')
            ->withConsecutive(
                ['foo_id', 123],
                ['bar_id', 456]
            )
            ->will($this->returnSelf());

        $response = $this->getMock(ResponseInterface::class);

        $delegate = $this->getMock(DelegateInterface::class);
        $delegate
            ->expects($this->never())
            ->method('process');

        $router = $this->getMock(RouterInterface::class);
        $router
            ->expects($this->once())
            ->method('match')
            ->with($path)
            ->willReturn([
                ['GET' => DispatcherTestMiddleware::class],
                ['foo_id' => 123, 'bar_id' => 456]
            ]);

        $container = $this->getMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('get')
            ->with(DispatcherTestMiddleware::class)
            ->willReturn(new DispatcherTestMiddleware($response));

        $dispatcher = new Dispatcher($router, $container);

        $this->assertSame($response, $dispatcher->process($request, $delegate));
    }

    /**
     * @expectedException Emonkak\HttpException\MethodNotAllowedHttpException
     */
    public function testMethodNotAllowed()
    {
        $path = '/foo/123/bar/456';

        $request = $this->createMockRequest('POST', $path);

        $response = $this->getMock(ResponseInterface::class);

        $delegate = $this->getMock(DelegateInterface::class);
        $delegate
            ->expects($this->never())
            ->method('process');

        $router = $this->getMock(RouterInterface::class);
        $router
            ->expects($this->once())
            ->method('match')
            ->with($path)
            ->willReturn([
                ['GET' => DispatcherTestMiddleware::class],
                ['foo_id' => 123, 'bar_id' => 456]
            ]);

        $container = $this->getMock(ContainerInterface::class);

        $dispatcher = new Dispatcher($router, $container);

        $this->assertSame($response, $dispatcher->process($request, $delegate));
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

class DispatcherTestController
{
    private $response;

    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
    }

    public function show(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        return $this->response;
    }
}

class DispatcherTestMiddleware implements ServerMiddlewareInterface
{
    private $response;

    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        return $this->response;
    }
}
