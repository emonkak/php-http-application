<?php

declare(strict_types=1);

namespace Emonkak\HttpApplication\Tests\Middleware;

use Emonkak\HttpApplication\PostDispatcher;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @covers \Emonkak\HttpApplication\PostDispatcher
 */
class PostDispatcherTest extends TestCase
{
    public function testControllerMatched()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request
            ->expects($this->once())
            ->method('getAttribute')
            ->with('__handler_reference')
            ->willReturn([PostDispatcherTestController::class, 'show']);

        $response = $this->createMock(ResponseInterface::class);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler
            ->expects($this->never())
            ->method('handle');

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('get')
            ->with(PostDispatcherTestController::class)
            ->willReturn(new PostDispatcherTestController($response));

        $dispatcher = new PostDispatcher($container);

        $this->assertSame($response, $dispatcher->process($request, $handler));
    }

    public function testMiddlewareMatched()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request
            ->expects($this->once())
            ->method('getAttribute')
            ->with('__handler_reference')
            ->willReturn(PostDispatcherTestMiddleware::class);

        $response = $this->createMock(ResponseInterface::class);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler
            ->expects($this->never())
            ->method('handle');

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('get')
            ->with(PostDispatcherTestMiddleware::class)
            ->willReturn(new PostDispatcherTestMiddleware($response));

        $dispatcher = new PostDispatcher($container);

        $this->assertSame($response, $dispatcher->process($request, $handler));
    }
}

class PostDispatcherTestController
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

class PostDispatcherTestMiddleware implements RequestHandlerInterface
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
