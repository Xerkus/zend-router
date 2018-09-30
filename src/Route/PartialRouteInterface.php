<?php
/**
 *  @see       https://github.com/zendframework/zend-router for the canonical source repository
 *  @copyright Copyright (c) 2015-2018 Zend Technologies USA Inc. (https://www.zend.com)
 *  @license   https://github.com/zendframework/zend-router/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Router\Route;

use Zend\Router\RouteInterface;

/**
 * Route capable of performing partial match before delegating to
 * chained route.
 *
 * Immutable interface is not used to minimize performance impact
 * of having to clone every parent route in chain in order to
 * programmatically modify configured routes in router.
 * Mutability makes route instance reuse potentially unsafe.
 */
interface PartialRouteInterface extends RouteInterface
{
    public function getChainedRoute() : RouteInterface;

    public function setChainedRoute(RouteInterface $route) : void;
}
