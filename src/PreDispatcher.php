<?php

declare(strict_types=1);

namespace Emonkak\HttpApplication;

use Emonkak\HttpException\MethodNotAllowedHttpException;
use Emonkak\HttpException\NotFoundHttpException;
use Emonkak\Router\RouterInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class PreDispatcher implements MiddlewareInterface
{
    /**
     * @var ResponseFactoryInterface
     */
    private $responseFactory;

    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        RouterInterface $router
    ) {
        $this->responseFactory = $responseFactory;
        $this->router = $router;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        $match = $this->router->match($path);

        if ($match === null) {
            throw new NotFoundHttpException('No route matches.');
        }

        list($handlers, $params) = $match;
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

        $request = $request->withAttribute('__handler_reference', $handlerReference);

        return $handler->handle($request);
    }
}
