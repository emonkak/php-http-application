<?php

namespace Emonkak\HttpMiddleware\Tests\Internal;

use Emonkak\HttpMiddleware\Internal\Pipeline;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;

/**
 * @covers Emonkak\HttpMiddleware\Internal\Pipeline
 */
class PipelineTest extends TestCase
{
    public function testProcess()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $middlewares = [
            $this->createMock(MiddlewareInterface::class),
            $this->createMock(MiddlewareInterface::class),
            $this->createMock(MiddlewareInterface::class),
        ];

        $queue = new \SplQueue();
        $pipeline = new Pipeline($queue);
        $handler = function($request, $handler) {
            return $handler->handle($request);
        };

        $middlewares[0]
            ->expects($this->once())
            ->method('process')
            ->with(
                $this->identicalTo($request),
                $this->identicalTo($pipeline)
            )
            ->will($this->returnCallback($handler));
        $middlewares[1]
            ->expects($this->once())
            ->method('process')
            ->with(
                $this->identicalTo($request),
                $this->identicalTo($pipeline)
            )
            ->will($this->returnCallback($handler));
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

        $this->assertSame($response, $pipeline->handle($request));
    }

    /**
     * @expectedException Emonkak\HttpException\NotFoundHttpException
     */
    public function testProcessThrowsHttpExceptionInterface()
    {
        $request = $this->createMock(ServerRequestInterface::class);

        $queue = new \SplQueue();
        $errorPipeline = new Pipeline($queue);

        $errorPipeline->handle($request);
    }
}
