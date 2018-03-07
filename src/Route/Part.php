<?php
/**
 * @link      http://github.com/zendframework/zend-router for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

declare(strict_types=1);

namespace Zend\Router\Route;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\UriInterface;
use SplQueue;
use Zend\Router\Exception;
use Zend\Router\Exception\InvalidArgumentException;
use Zend\Router\Route\Partial\Terminable;
use Zend\Router\RouteInterface;
use Zend\Router\RouteResult;
use Zend\Router\RouteStackInterface;
use Zend\Router\TreeRouteStack;

use function get_class;
use function gettype;
use function is_object;
use function sprintf;

/**
 * Part route.
 */
final class Part implements RouteStackInterface
{
    /**
     * Chained partial routes
     *
     * @var SplQueue
     */
    private $chain;

    /**
     * Whether the route may terminate.
     *
     * @var bool
     */
    private $mayTerminate;

    /**
     * Child routes.
     *
     * @var RouteStackInterface
     */
    private $childRoutes;

    /**
     * Create a new part route.
     *
     * @param PartialRouteInterface[] $chainedRoutes
     * @param RouteInterface[]|RouteStackInterface $childRoutes
     */
    public function __construct(
        PartialRouteInterface $route,
        array $chainedRoutes,
        $childRoutes,
        bool $mayTerminate = false
    ) {
        $this->chain = new SplQueue();

        $this->chain->enqueue($route);
        foreach ($chainedRoutes as $chainRoute) {
            if (! $chainRoute instanceof PartialRouteInterface) {
                throw new InvalidArgumentException(sprintf(
                    'Chained route must be instance of %s but %s given',
                    PartialRouteInterface::class,
                    is_object($chainRoute) ? get_class($chainRoute) : gettype($chainRoute)
                ));
            }
            $this->chain->enqueue($chainRoute);
        }

        $this->mayTerminate = $mayTerminate;
        if ($this->mayTerminate) {
            $this->chain->enqueue(Terminable::getInstance());
        }

        if (! $childRoutes instanceof RouteStackInterface) {
            $stack = new TreeRouteStack();
            $stack->setRoutes($childRoutes);
            $childRoutes = $stack;
        }
        $this->childRoutes = $childRoutes;
    }

    /**
     * Match a given request.
     *
     * @throws InvalidArgumentException on negative path offset
     */
    public function match(Request $request, int $pathOffset = 0, array $options = []) : RouteResult
    {
        if ($pathOffset < 0) {
            throw new InvalidArgumentException('Path offset cannot be negative');
        }
        if ($this->chain->isEmpty()) {
            return $this->childRoutes->match($request, $pathOffset, $options);
        }

        $next = clone $this;
        /** @var PartialRouteInterface $route */
        $route = $next->chain->dequeue();

        return $route->matchPartial($request, $next, $pathOffset, $options);
    }

    /**
     * Assemble uri for the route.
     *
     * @throws Exception\RuntimeException when trying to assemble part route without
     *     child route name, if part route can't terminate
     */
    public function assemble(UriInterface $uri, array $substitutions = [], array $options = []) : UriInterface
    {
        if ($this->chain->isEmpty()) {
            // do this check only when we reach end of chain
            if (! $this->mayTerminate && empty($options['name'])) {
                throw new Exception\RuntimeException('Part route may not terminate');
            }
            return $this->childRoutes->assemble($uri, $substitutions, $options);
        }

        $next = clone $this;
        /** @var PartialRouteInterface $route */
        $route = $next->chain->dequeue();

        return $route->assemblePartial($uri, $next, $substitutions, $options);
    }

    /**
     * Add a route to the stack.
     */
    public function addRoute(string $name, RouteInterface $route, ?int $priority = null) : void
    {
        $this->childRoutes->addRoute($name, $route, $priority);
    }

    /**
     * Add multiple routes to the stack.
     *
     * @param RouteInterface[] $routes
     */
    public function addRoutes(iterable $routes) : void
    {
        $this->childRoutes->addRoutes($routes);
    }

    /**
     * Remove a route from the stack.
     */
    public function removeRoute(string $name) : void
    {
        $this->childRoutes->removeRoute($name);
    }

    /**
     * Remove all routes from the stack and set new ones.
     *
     * @param RouteInterface[] $routes
     */
    public function setRoutes(iterable $routes) : void
    {
        $this->childRoutes->setRoutes($routes);
    }

    /**
     * Get the added routes
     *
     * @return RouteInterface[] Route map $name => $route
     */
    public function getRoutes() : array
    {
        return $this->childRoutes->getRoutes();
    }

    /**
     * Get a route by name
     */
    public function getRoute(string $name) : ?RouteInterface
    {
        return $this->childRoutes->getRoute($name);
    }

    public function __clone()
    {
        $this->chain = clone $this->chain;
    }
}
