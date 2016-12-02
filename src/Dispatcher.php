<?php

namespace Emonkak\HttpMiddleware;

use Emonkak\HttpException\MethodNotAllowedHttpException;
use Emonkak\Router\RouterInterface;
use Interop\Container\ContainerInterface;
use Interop\Http\Middleware\DelegateInterface;
use Interop\Http\Middleware\ServerMiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;

class Dispatcher implements ServerMiddlewareInterface
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param RouterInterface    $router
     * @param ContainerInterface $container
     */
    public function __construct(
        RouterInterface $router,
        ContainerInterface $container
    ) {
        $this->router = $router;
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $match = $this->router->match($request->getUri()->getPath());
        if ($match === null) {
            return $delegate->process($request);
        }

        list ($handlers, $params) = $match;
        $method = strtoupper($request->getMethod());

        if (!isset($handlers[$method])) {
            throw new MethodNotAllowedHttpException(array_keys($handlers));
        }

        foreach ($params as $name => $value) {
            $request = $request->withAttribute($name, $value);
        }

        $handler = $handlers[$method];

        if (is_array($handler)) {
            list ($class, $method) = $handler;

            $instance = $this->container->get($class);

            return $instance->$method($request, $delegate);
        } else {
            $middleware = $this->container->get($handler);

            return $middleware->process($request, $delegate);
        }
    }
}
