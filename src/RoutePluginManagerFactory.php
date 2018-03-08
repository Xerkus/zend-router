<?php
/**
 *  @see       https://github.com/zendframework/zend-router for the canonical source repository
 *  @copyright Copyright (c) 2015-2018 Zend Technologies USA Inc. (https://www.zend.com)
 *  @license   https://github.com/zendframework/zend-router/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Router;

use Psr\Container\ContainerInterface;

class RoutePluginManagerFactory
{
    /**
     * Create and return a route plugin manager.
     */
    public function __invoke(ContainerInterface $container) : RoutePluginManager
    {
        $config = static::getRoutesConfig($container);
        return new RoutePluginManager($container, $config);
    }

    public static function getRoutesConfig(ContainerInterface $container) : array
    {
        if (! $container->has('config')) {
            return [];
        }
        return $container->get('config')[RoutePluginManager::class] ?? [];
    }
}
