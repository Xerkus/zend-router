<?php
/**
 * @link      http://github.com/zendframework/zend-router for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

declare(strict_types=1);

namespace Zend\Router;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\UriInterface;
use Zend\Router\Exception\InvalidArgumentException;
use Zend\Router\Exception\RuntimeException;

use function array_merge;
use function sprintf;

/**
 * Simple route stack implementation.
 */
class SimpleRouteStack implements RouteStackInterface
{
    /**
     * Stack containing all routes.
     *
     * @var PriorityList
     */
    protected $routes;

    /**
     * Default parameters.
     *
     * @var array
     */
    protected $defaultParams = [];

    /**
     * Create a new simple route stack.
     */
    public function __construct()
    {
        $this->routes = new PriorityList();
    }

    /**
     * @return RouteInterface[] Route map $name => $route
     */
    public function getRoutes() : array
    {
        return $this->routes->toArray($this->routes::EXTR_DATA);
    }

    /**
     * Remove all routes from the stack and set new ones.
     *
     * @param  RouteInterface[] $routes Route map $name => $route
     */
    public function setRoutes(iterable $routes) : void
    {
        $this->routes->clear();
        $this->addRoutes($routes);
    }

    /**
     * Add multiple routes to the stack.
     *
     * @param  RouteInterface[] $routes Route map $name => $route
     */
    public function addRoutes(iterable $routes) : void
    {
        foreach ($routes as $name => $route) {
            $this->addRoute($name, $route);
        }
    }

    /**
     * Add a route to the stack.
     */
    public function addRoute(string $name, RouteInterface $route, ?int $priority = null) : void
    {
        if ($priority === null && isset($route->priority)) {
            $priority = $route->priority;
        }

        $this->routes->insert($name, $route, $priority);
    }

    /**
     * Get route by name
     */
    public function getRoute(string $name) : ?RouteInterface
    {
        return $this->routes->get($name);
    }

    /**
     * Remove a route from the stack.
     */
    public function removeRoute(string $name) : void
    {
        $this->routes->remove($name);
    }


    /**
     * Set a default parameters.
     *
     * @param mixed[] $params
     */
    public function setDefaultParams(array $params) : void
    {
        $this->defaultParams = $params;
    }

    /**
     * Set a default parameter.
     *
     * @param  mixed  $value
     */
    public function setDefaultParam(string $name, $value) : void
    {
        $this->defaultParams[$name] = $value;
    }

    /**
     * Match a given request.
     *
     * @param int $pathOffset URI path offset to use for matching
     */
    public function match(Request $request, int $pathOffset = 0, array $options = []) : RouteResult
    {
        $methodFailureResults = [];
        foreach ($this->routes as $name => $route) {
            /** @var RouteInterface $route */
            $result = $route->match($request, $pathOffset, $options);
            if ($result->isSuccess()) {
                $result = $result->withMatchedRouteName($name);
                if (empty($this->defaultParams)) {
                    return $result;
                }
                return $result->withMatchedParams($result->getMatchedParams() + $this->defaultParams);
            }
            if ($result->isMethodFailure()) {
                $methodFailureResults[] = $result;
            }
        }
        if (! empty($methodFailureResults)) {
            // micro optimisation
            if (! isset($methodFailureResults[1])) {
                return $methodFailureResults[0];
            }
            $methods = [];
            foreach ($methodFailureResults as $failureResult) {
                /** @var RouteResult $failureResult */
                $methods = array_merge($methods, $failureResult->getAllowedMethods());
            }
            RouteResult::fromMethodFailure($methods);
        }
        return RouteResult::fromRouteFailure();
    }

    /**
     * Generate a URI
     *
     * @param UriInterface $uri Base URI instance. Assembled URI path should
     *      append to path present in base URI.
     * @throws RuntimeException if unable to generate the given URI
     * @throws InvalidArgumentException If required option "name" is not present
     */
    public function assemble(UriInterface $uri, array $substitutions = [], array $options = []) : UriInterface
    {
        if (! isset($options['name'])) {
            throw new InvalidArgumentException('Missing "name" option');
        }
        $name = $options['name'];
        unset($options['name']);

        $route = $this->routes->get($name);
        if (! $route) {
            throw new RuntimeException(sprintf('Route with name "%s" not found', $name));
        }

        return $route->assemble($uri, array_merge($this->defaultParams, $substitutions), $options);
    }
}
