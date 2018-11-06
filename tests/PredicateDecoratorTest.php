<?php

declare(strict_types=1);

namespace Emonkak\HttpMiddleware\Tests;

use Emonkak\HttpMiddleware\PredicateDecorator;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @covers Emonkak\HttpMiddleware\PredicateDecorator
 */
class PredicateDecoratorTest extends TestCase
{
    public function testFulfilled()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $handler = $this->createMock(RequestHandlerInterface::class);

        $middleware = $this->createMock(MiddlewareInterface::class);
        $middleware
            ->expects($this->once())
            ->method('process')
            ->with(
                $this->identicalTo($request),
                $this->identicalTo($handler)
            )
            ->willReturn($response);

        $predicate = function(ServerRequestInterface $request) {
            return true;
        };
        $decorator = new PredicateDecorator($middleware, $predicate);

        $this->assertSame($response, $decorator->process($request, $handler));
    }

    public function testNotFulfilled()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler
            ->expects($this->once())
            ->method('handle')
            ->with($this->identicalTo($request))
            ->willReturn($response);

        $middleware = $this->createMock(MiddlewareInterface::class);
        $middleware
            ->expects($this->never())
            ->method('process');

        $predicate = function(ServerRequestInterface $request) {
            return false;
        };
        $decorator = new PredicateDecorator($middleware, $predicate);

        $this->assertSame($response, $decorator->process($request, $handler));
    }
}
