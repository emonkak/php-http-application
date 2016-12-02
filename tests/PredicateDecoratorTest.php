<?php

namespace Emonkak\HttpMiddleware\Tests;

use Emonkak\HttpMiddleware\PredicateDecorator;
use Interop\Http\Middleware\DelegateInterface;
use Interop\Http\Middleware\ServerMiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @covers Emonkak\HttpMiddleware\PredicateDecorator
 */
class PredicateDecoratorTest extends \PHPUnit_Framework_TestCase
{
    public function testFulfilled()
    {
        $request = $this->getMock(ServerRequestInterface::class);
        $response = $this->getMock(ResponseInterface::class);

        $delegate = $this->getMock(DelegateInterface::class);

        $middleware = $this->getMock(ServerMiddlewareInterface::class);
        $middleware
            ->expects($this->once())
            ->method('process')
            ->with(
                $this->identicalTo($request),
                $this->identicalTo($delegate)
            )
            ->willReturn($response);

        $predicate = function(ServerRequestInterface $request) {
            return true;
        };
        $decorator = new PredicateDecorator($middleware, $predicate);

        $this->assertSame($response, $decorator->process($request, $delegate));
    }

    public function testNotFulfilled()
    {
        $request = $this->getMock(ServerRequestInterface::class);
        $response = $this->getMock(ResponseInterface::class);

        $delegate = $this->getMock(DelegateInterface::class);
        $delegate
            ->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($request))
            ->willReturn($response);

        $middleware = $this->getMock(ServerMiddlewareInterface::class);
        $middleware
            ->expects($this->never())
            ->method('process');

        $predicate = function(ServerRequestInterface $request) {
            return false;
        };
        $decorator = new PredicateDecorator($middleware, $predicate);

        $this->assertSame($response, $decorator->process($request, $delegate));
    }
}
