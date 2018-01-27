<?php

namespace Emonkak\HttpMiddleware;

use Emonkak\HttpException\HttpExceptionInterface;
use Emonkak\HttpException\InternalServerErrorHttpException;
use Emonkak\HttpMiddleware\Internal\ErrorPipeline;
use Emonkak\HttpMiddleware\Internal\Pipeline;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Application implements ErrorHandlerInterface, RequestHandlerInterface
{
    /**
     * @var MiddlewareInterface[]
     */
    private $middlewares = [];

    /**
     * @var ErrorMiddlewareInterface[]
     */
    private $errorMiddlewares = [];

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $pipeline = new Pipeline($this->middlewares);

        try {
            return $pipeline->handle($request);
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
    public function handleError(ServerRequestInterface $request, HttpExceptionInterface $exception): ResponseInterface
    {
        $pipeline = new ErrorPipeline($this->errorMiddlewares);

        return $pipeline->handleError($request, $exception);
    }

    /**
     * @param MiddlewareInterface $middleware
     * @return $this
     */
    public function pipe(MiddlewareInterface $middleware): Application
    {
        $this->middlewares[] = $middleware;

        return $this;
    }

    /**
     * @param MiddlewareInterface $middleware
     * @param callable            $predicate
     * @return $this
     */
    public function pipeIf(MiddlewareInterface $middleware, callable $predicate): Application
    {
        return $this->pipe(new PredicateDecorator($middleware, $predicate));
    }

    /**
     * @param MiddlewareInterface $middleware
     * @param string              $path
     * @return $this
     */
    public function pipeOn(MiddlewareInterface $middleware, $path): Application
    {
        return $this->pipeIf($middleware, static function(ServerRequestInterface $request) use ($path) {
            return strpos($request->getUri()->getPath(), $path) === 0;
        });
    }

    /**
     * @param MiddlewareInterface $middleware
     * @return $this
     */
    public function pipeOnError(ErrorMiddlewareInterface $errorMiddleware): Application
    {
        $this->errorMiddlewares[] = $errorMiddleware;

        return $this;
    }
}
