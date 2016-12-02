<?php

namespace Emonkak\HttpMiddleware\Tests\Internal;

use Emonkak\HttpMiddleware\Internal\Pipeline;
use Interop\Http\Middleware\ServerMiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @covers Emonkak\HttpMiddleware\Internal\Pipeline
 */
class PipelineTest extends \PHPUnit_Framework_TestCase
{
    public function testProcess()
    {
        $request = $this->getMock(ServerRequestInterface::class);
        $response = $this->getMock(ResponseInterface::class);

        $middlewares = [
            $this->getMock(ServerMiddlewareInterface::class),
            $this->getMock(ServerMiddlewareInterface::class),
            $this->getMock(ServerMiddlewareInterface::class),
        ];

        $queue = new \SplQueue();
        $pipeline = new Pipeline($queue);
        $delegate = function($request, $delegate) {
            return $delegate->process($request);
        };

        $middlewares[0]
            ->expects($this->once())
            ->method('process')
            ->with(
                $this->identicalTo($request),
                $this->identicalTo($pipeline)
            )
            ->will($this->returnCallback($delegate));
        $middlewares[1]
            ->expects($this->once())
            ->method('process')
            ->with(
                $this->identicalTo($request),
                $this->identicalTo($pipeline)
            )
            ->will($this->returnCallback($delegate));
        $middlewares[2]
            ->expects($this->once())
            ->method('process')
            ->with(
                $this->identicalTo($request),
                $this->identicalTo($pipeline)
            )
            ->willReturn($response);

        foreach ($middlewares as $middleware) {
            $queue->enqueue($middleware);
        }

        $this->assertSame($response, $pipeline->process($request));
    }

    /**
     * @expectedException Emonkak\HttpException\NotFoundHttpException
     */
    public function testProcessThrowsHttpExceptionInterface()
    {
        $request = $this->getMock(ServerRequestInterface::class);

        $queue = new \SplQueue();
        $errorPipeline = new Pipeline($queue);

        $errorPipeline->process($request);
    }
}
