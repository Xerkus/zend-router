<?php
/**
 * @link      http://github.com/zendframework/zend-router for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

declare(strict_types=1);

namespace Zend\Router;

/**
 * Immutable interface is not used to minimize performance impact
 * of having to clone every parent route in chain in order to
 * programmatically modify configured routes in router.
 * Mutability makes route instance reuse potentially unsafe.
 */
interface RouteStackInterface extends RouteInterface
{
    /**
     * Add a route to the stack.
     */
    public function addRoute(string $name, RouteInterface $route, ?int $priority = null) : void;

    /**
     * Get route by name
     */
    public function getRoute(string $name) : ?RouteInterface;

    /**
     * Remove a route from the stack.
     */
    public function removeRoute(string $name) : void;

    /**
     * Add multiple routes to the stack.
     *
     * @param  RouteInterface[] $routes Route map $name => $route
     */
    public function addRoutes(iterable $routes) : void;

    /**
     * Remove all routes from the stack and set new ones.
     *
     * @param  RouteInterface[] $routes Route map $name => $route
     */
    public function setRoutes(iterable $routes) : void;

    /**
     * @return RouteInterface[] Route map $name => $route
     */
    public function getRoutes() : array;
}
