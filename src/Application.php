<?php

namespace Emonkak\HttpMiddleware;

use Emonkak\HttpException\HttpExceptionInterface;
use Emonkak\HttpException\InternalServerErrorHttpException;
use Emonkak\HttpMiddleware\Internal\ErrorPipeline;
use Emonkak\HttpMiddleware\Internal\Pipeline;
use Interop\Http\Middleware\ServerMiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Application
{
    /**
     * @var \SplQueue
     */
    private $middlewares;

    /**
     * @var \SplQueue
     */
    private $errorMiddlewares;

    public function __construct()
    {
        $this->middlewares = new \SplQueue();
        $this->errorMiddlewares = new \SplQueue();
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request)
    {
        $pipeline = new Pipeline(clone $this->middlewares);

        try {
            return $pipeline->process($request);
        } catch (HttpExceptionInterface $e) {
            return $this->handleError($request, $e);
        } catch (\Exception $e) {
            return $this->handleError($request, new InternalServerErrorHttpException(null, $e));
        }
    }

    /**
     * @param ServerRequestInterface $request
     * @param HttpExceptionInterface $exception
     * @return ResponseInterface
     */
    public function handleError(ServerRequestInterface $request, HttpExceptionInterface $exception)
    {
        $pipeline = new ErrorPipeline(clone $this->errorMiddlewares);

        return $pipeline->processError($request, $exception);
    }

    /**
     * @param ServerMiddlewareInterface $middleware
     * @return $this
     */
    public function pipe(ServerMiddlewareInterface $middleware)
    {
        $this->middlewares->enqueue($middleware);

        return $this;
    }

    /**
     * @param ServerMiddlewareInterface $middleware
     * @param callable                  $predicate
     * @return $this
     */
    public function pipeIf(ServerMiddlewareInterface $middleware, callable $predicate)
    {
        return $this->pipe(new PredicateDecorator($middleware, $predicate));
    }

    /**
     * @param ServerMiddlewareInterface $middleware
     * @param string                    $path
     * @return $this
     */
    public function pipeOn(ServerMiddlewareInterface $middleware, $path)
    {
        return $this->pipeIf($middleware, static function(ServerRequestInterface $request) use ($path) {
            return strpos($request->getUri()->getPath(), $path) === 0;
        });
    }

    /**
     * @param ServerMiddlewareInterface $middleware
     * @return $this
     */
    public function pipeOnError(ErrorMiddlewareInterface $errorMiddleware)
    {
        $this->errorMiddlewares->enqueue($errorMiddleware);

        return $this;
    }
}
