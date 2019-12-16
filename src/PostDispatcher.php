<?php

declare(strict_types=1);

namespace Emonkak\HttpMiddleware;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class PostDispatcher implements MiddlewareInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(
        ContainerInterface $container
    ) {
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $handlerReference = $request->getAttribute('__handler_reference');

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
