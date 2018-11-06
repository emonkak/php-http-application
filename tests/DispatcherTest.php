<?php

declare(strict_types=1);

namespace Emonkak\HttpMiddleware\Tests\Middleware;

use Emonkak\HttpMiddleware\Dispatcher;
use Emonkak\Router\RouterInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @covers Emonkak\HttpMiddleware\Dispatcher
 */
class DispatcherTest extends TestCase
{
    public function testNotMatched()
    {
        $path = '/foo/123';

        $request = $this->createMockRequest('GET', $path);
        $response = $this->createMock(ResponseInterface::class);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler
            ->expects($this->once())
            ->method('handle')
            ->with($this->identicalTo($request))
            ->willReturn($response);

        $responseFactory = $this->createMock(ResponseFactoryInterface::class);
        $responseFactory
            ->expects($this->never())
            ->method('createResponse');

        $container = $this->createMock(ContainerInterface::class);

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->once())
            ->method('match')
            ->with($path)
            ->willReturn(null);

        $dispatcher = new Dispatcher($container, $responseFactory, $router);

        $this->assertSame($response, $dispatcher->process($request, $handler));
    }

    public function testControllerMatched()
    {
        $path = '/foo/123/bar/456/baz/%E3%81%82%E3%81%84%E3%81%86%E3%81%88%E3%81%8A';

        $request = $this->createMockRequest('GET', $path);
        $request
            ->expects($this->exactly(3))
            ->method('withAttribute')
            ->withConsecutive(
                ['foo_id', '123'],
                ['bar_id', '456'],
                ['baz_id', 'あいうえお']
            )
            ->will($this->returnSelf());

        $response = $this->createMock(ResponseInterface::class);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler
            ->expects($this->never())
            ->method('handle');

        $responseFactory = $this->createMock(ResponseFactoryInterface::class);
        $responseFactory
            ->expects($this->never())
            ->method('createResponse');

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('get')
            ->with(DispatcherTestController::class)
            ->willReturn(new DispatcherTestController($response));

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->once())
            ->method('match')
            ->with('/foo/123/bar/456/baz/%E3%81%82%E3%81%84%E3%81%86%E3%81%88%E3%81%8A')
            ->willReturn([
                ['GET' => [DispatcherTestController::class, 'show']],
                ['foo_id' => '123', 'bar_id' => '456', 'baz_id' => '%E3%81%82%E3%81%84%E3%81%86%E3%81%88%E3%81%8A']
            ]);

        $dispatcher = new Dispatcher($container, $responseFactory, $router);

        $this->assertSame($response, $dispatcher->process($request, $handler));
    }

    public function testMiddlewareMatched()
    {
        $path = '/foo/123/bar/456';

        $request = $this->createMockRequest('GET', $path);
        $request
            ->expects($this->exactly(2))
            ->method('withAttribute')
            ->withConsecutive(
                ['foo_id', '123'],
                ['bar_id', '456']
            )
            ->will($this->returnSelf());

        $response = $this->createMock(ResponseInterface::class);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler
            ->expects($this->never())
            ->method('handle');

        $responseFactory = $this->createMock(ResponseFactoryInterface::class);
        $responseFactory
            ->expects($this->never())
            ->method('createResponse');

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('get')
            ->with(DispatcherTestMiddleware::class)
            ->willReturn(new DispatcherTestMiddleware($response));

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->once())
            ->method('match')
            ->with($path)
            ->willReturn([
                ['GET' => DispatcherTestMiddleware::class],
                ['foo_id' => '123', 'bar_id' => '456']
            ]);

        $dispatcher = new Dispatcher($container, $responseFactory, $router);

        $this->assertSame($response, $dispatcher->process($request, $handler));
    }

    public function testHeadRequest()
    {
        $path = '/foo/123/bar/456';

        $request = $this->createMockRequest('HEAD', $path);
        $request
            ->expects($this->exactly(2))
            ->method('withAttribute')
            ->withConsecutive(
                ['foo_id', '123'],
                ['bar_id', '456']
            )
            ->will($this->returnSelf());

        $response = $this->createMock(ResponseInterface::class);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler
            ->expects($this->never())
            ->method('handle');

        $responseFactory = $this->createMock(ResponseFactoryInterface::class);
        $responseFactory
            ->expects($this->never())
            ->method('createResponse');

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('get')
            ->with(DispatcherTestMiddleware::class)
            ->willReturn(new DispatcherTestMiddleware($response));

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->once())
            ->method('match')
            ->with($path)
            ->willReturn([
                ['GET' => DispatcherTestMiddleware::class],
                ['foo_id' => '123', 'bar_id' => '456']
            ]);

        $dispatcher = new Dispatcher($container, $responseFactory, $router);

        $this->assertSame($response, $dispatcher->process($request, $handler));
    }

    public function testFalllbackGetRequest()
    {
        $path = '/foo/123/bar/456';

        $request = $this->createMockRequest('HEAD', $path);
        $request
            ->expects($this->exactly(2))
            ->method('withAttribute')
            ->withConsecutive(
                ['foo_id', '123'],
                ['bar_id', '456']
            )
            ->will($this->returnSelf());

        $response = $this->createMock(ResponseInterface::class);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler
            ->expects($this->never())
            ->method('handle');

        $responseFactory = $this->createMock(ResponseFactoryInterface::class);
        $responseFactory
            ->expects($this->never())
            ->method('createResponse');

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('get')
            ->with(DispatcherTestMiddleware::class)
            ->willReturn(new DispatcherTestMiddleware($response));

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->once())
            ->method('match')
            ->with($path)
            ->willReturn([
                ['HEAD' => DispatcherTestMiddleware::class],
                ['foo_id' => '123', 'bar_id' => '456']
            ]);

        $dispatcher = new Dispatcher($container, $responseFactory, $router);

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
            ->with($this->identicalTo('Allow'), $this->identicalTo('GET, HEAD'))
            ->will($this->returnSelf());

        $responseFactory = $this->createMock(ResponseFactoryInterface::class);
        $responseFactory
            ->expects($this->once())
            ->method('createResponse')
            ->willReturn($response);

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->never())
            ->method('get');

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->once())
            ->method('match')
            ->with($path)
            ->willReturn([
                ['GET' => DispatcherTestMiddleware::class],
                ['foo_id' => '123', 'bar_id' => '456']
            ]);

        $dispatcher = new Dispatcher($container, $responseFactory, $router);

        $this->assertSame($response, $dispatcher->process($request, $handler));
    }

    /**
     * @expectedException Emonkak\HttpException\MethodNotAllowedHttpException
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

        $container = $this->createMock(ContainerInterface::class);

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->once())
            ->method('match')
            ->with($path)
            ->willReturn([
                ['GET' => DispatcherTestMiddleware::class],
                ['foo_id' => 123, 'bar_id' => 456]
            ]);

        $dispatcher = new Dispatcher($container, $responseFactory, $router);

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

class DispatcherTestController
{
    private $response;

    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
    }

    public function show(ServerRequestInterface $request): ResponseInterface
    {
        return $this->response;
    }
}

class DispatcherTestMiddleware implements RequestHandlerInterface
{
    private $response;

    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->response;
    }
}
