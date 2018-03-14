<?php
/**
 * @see       https://github.com/zendframework/zend-router for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-router/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Router\Container;

use Interop\Container\ContainerInterface;
use Zend\Router\RoutePluginManager;
use Zend\ServiceManager\Factory\FactoryInterface;

class RoutePluginManagerFactory implements FactoryInterface
{
    /**
     * Create and return a route plugin manager.
     *
     * @param string $name
     */
    public function __invoke(ContainerInterface $container, $name, array $options = null) : RoutePluginManager
    {
        $options = $options ?: $this->getRoutesConfig($container);
        return new RoutePluginManager($container, $options);
    }

    public function getRoutesConfig(ContainerInterface $container) : array
    {
        if (! $container->has('config')) {
            return [];
        }
        return $container->get('config')[RoutePluginManager::class] ?? [];
    }
}
