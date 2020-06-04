<?php

declare(strict_types=1);

namespace Emonkak\HttpApplication;

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

    public function __construct(
        ContainerInterface $container
    ) {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $handlerReference = $request->getAttribute('__handler_reference');
        if ($handlerReference === null) {
            throw new \RuntimeException('The handler reference is not defined. You need to call `PreDispatcher` before execution of `PostDispatcher`.');
        }

        if (is_array($handlerReference)) {
            list($class, $method) = $handlerReference;

            $instance = $this->container->get($class);

            return $instance->$method($request);
        } else {
            $handler = $this->container->get($handlerReference);

            return $handler->handle($request);
        }
    }
}
