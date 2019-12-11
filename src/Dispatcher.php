<?php

declare(strict_types=1);

namespace Emonkak\HttpMiddleware;

use Emonkak\HttpException\MethodNotAllowedHttpException;
use Emonkak\Router\RouterInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Dispatcher implements MiddlewareInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var ResponseFactoryInterface
     */
    private $responseFactory;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @param ContainerInterface       $container
     * @param ResponseFactoryInterface $responseFactory
     * @param RouterInterface          $router
     */
    public function __construct(
        ContainerInterface $container,
        ResponseFactoryInterface $responseFactory,
        RouterInterface $router
    ) {
        $this->container = $container;
        $this->responseFactory = $responseFactory;
        $this->router = $router;
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

        if (isset($handlers[$method])) {
            $handlerReference = $handlers[$method];
        } else {
            if ($method === 'OPTIONS') {
                $allowedMethods = array_keys($handlers);

                if (in_array('GET', $allowedMethods) && !in_array('HEAD', $allowedMethods)) {
                    $allowedMethods[] = 'HEAD';
                }

                $allowedMethods[] = 'OPTIONS';

                return $this->responseFactory
                    ->createResponse(204)
                    ->withHeader('Allow', implode(', ', $allowedMethods));
            }

            if ($method === 'HEAD' && isset($handlers['GET'])) {
                $handlerReference = $handlers['GET'];
            } else {
                throw new MethodNotAllowedHttpException(array_keys($handlers));
            }
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
