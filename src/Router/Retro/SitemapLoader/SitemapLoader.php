<?php

/*
 * This file is part of the Active Collab Bootstrap project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Bootstrap\Router\Retro\SitemapLoader;

use ActiveCollab\Bootstrap\Router\Retro\Nodes\Directory\DirectoryInterface;
use ActiveCollab\Bootstrap\Router\Retro\Pathfinder\PathfinderInterface;
use ActiveCollab\Bootstrap\Router\Retro\Router;
use ActiveCollab\Bootstrap\SitemapPathResolver\SitemapPathResolverInterface;
use Psr\Http\Server\MiddlewareInterface;
use Slim\Interfaces\RouteCollectorProxyInterface;

class SitemapLoader implements SitemapLoaderInterface
{
    private $sitemapPathResolver;
    private $pathfinder;

    public function __construct(
        SitemapPathResolverInterface $sitemapPathResolver,
        PathfinderInterface $pathfinder
    )
    {
        $this->sitemapPathResolver = $sitemapPathResolver;
        $this->pathfinder = $pathfinder;
    }

    public function loadRoutes(RouteCollectorProxyInterface $app): iterable
    {
        $routingRoot = (new Router())->scan($this->sitemapPathResolver->getSitemapPath());

        $routes = [];

        $this->loadDirRoutes($app, $routingRoot, $routes, '');

        return $routes;
    }

    private function loadDirRoutes(
        RouteCollectorProxyInterface $routeCollector,
        DirectoryInterface $directory,
        array &$routes,
        string $route_prefix
    ): void
    {
        foreach ($directory->getSubdirectories() as $subdirectory) {
            if ($this->pathfinder->hasRoute($subdirectory)) {
                $routeCollector->group(
                    $this->pathfinder->getRoutingPath($subdirectory),
                    function (RouteCollectorProxyInterface $proxy) use ($subdirectory, &$routes, $route_prefix) {
                        $this->loadDirRoutes(
                            $proxy,
                            $subdirectory,
                            $routes,
                            ($route_prefix ? $route_prefix . '_' : '') . $subdirectory->getNodeName()
                        );
                    }
                );
            }
        }

        $middlewareNode = $directory->getMiddleware();

        if ($middlewareNode) {
            $middlewares = require $middlewareNode->getPath();

            if (is_array($middlewares)) {
                foreach ($middlewares as $middleware) {
                    if ($middleware instanceof MiddlewareInterface) {
                        $routeCollector->add($middleware);
                    }
                }
            }
        }

        if ($directory->hasIndex()) {
            $handler = $this->pathfinder->getRouteHandler($directory->getIndex());

            if ($handler) {
                $routes[] = $routeCollector->any(
                    $this->pathfinder->getRoutingPath($directory->getIndex()),
                    $handler
                )->setName($route_prefix ? $route_prefix . '_index' : 'index');
            }
        }

        foreach ($directory->getFiles() as $file) {
            if ($file->isIndex()) {
                continue;
            }

            $handler = $this->pathfinder->getRouteHandler($file);

            if ($handler) {
                $routes[] = $routeCollector->any(
                    $this->pathfinder->getRoutingPath($file),
                    $handler
                )->setName(($route_prefix ? $route_prefix . '_' : '') . $file->getNodeName());
            }
        }
    }
}