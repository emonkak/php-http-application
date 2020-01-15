<?php

declare(strict_types=1);

namespace Emonkak\HttpMiddleware\Tests;

use Emonkak\HttpMiddleware\Application;
use Emonkak\HttpMiddleware\Pipeline;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;

/**
 * @covers \Emonkak\HttpMiddleware\Application
 */
class ApplicationTest extends TestCase
{
    public function testPipe()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $middleware = $this->createMock(MiddlewareInterface::class);
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
}
