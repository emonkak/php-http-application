<?php

namespace Emonkak\HttpMiddleware;

use Emonkak\HttpException\MethodNotAllowedHttpException;
use Emonkak\Router\RouterInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Dispatcher implements MiddlewareInterface
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
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        $match = $this->router->match($path);

        if ($match === null) {
            return $handler->handle($request);
        }

        list ($handlers, $params) = $match;
        $method = strtoupper($request->getMethod());

        if ($method === 'HEAD') {
            $handlerReference = isset($handlers[$method])
                ? $handlers[$method]
                : (isset($handlers['GET']) ? $handlers['GET'] : null);
        } else {
            $handlerReference = isset($handlers[$method]) ? $handlers[$method] : null;
        }

        if ($handlerReference === null) {
            throw new MethodNotAllowedHttpException(array_keys($handlers));
        }

        foreach ($params as $name => $value) {
            $request = $request->withAttribute($name, urldecode($value));
        }

        if (is_array($handlerReference)) {
            list ($class, $method) = $handlerReference;

            $instance = $this->container->get($class);

            return $instance->$method($request);
        } else {
            $handler = $this->container->get($handlerReference);

            return $handler->handle($request);
        }
    }
}
