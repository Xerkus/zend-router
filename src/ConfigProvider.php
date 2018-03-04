<?php
/**
 * @link      http://github.com/zendframework/zend-router for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

declare(strict_types=1);

namespace Zend\Router;

use Zend\Router\Route\Chain;
use Zend\Router\Route\Hostname;
use Zend\Router\Route\Literal;
use Zend\Router\Route\Method;
use Zend\Router\Route\Part;
use Zend\Router\Route\Placeholder;
use Zend\Router\Route\Regex;
use Zend\Router\Route\Scheme;
use Zend\Router\Route\Segment;

/**
 * Provide base configuration for using the component.
 *
 * Provides base configuration expected in order to:
 *
 * - seed and configure the default routers and route plugin manager.
 * - provide routes to the given routers.
 */
class ConfigProvider
{
    /**
     * Provide default configuration.
     *
     * @return array
     */
    public function __invoke()
    {
        return [
            'dependencies' => $this->getDependencyConfig(),
            'route_manager' => $this->getRouteManagerConfig(),
        ];
    }

    /**
     * Provide default container dependency configuration.
     *
     * @return array
     */
    public function getDependencyConfig()
    {
        return [
            'aliases' => [
                'HttpRouter' => TreeRouteStack::class,
                'router' => RouteStackInterface::class,
                'Router' => RouteStackInterface::class,
                'RoutePluginManager' => RoutePluginManager::class,
            ],
            'factories' => [
                TreeRouteStack::class => Route\HttpRouterFactory::class,
                RoutePluginManager::class => RoutePluginManagerFactory::class,
                RouteStackInterface::class => RouterFactory::class,
            ],
        ];
    }

    /**
     * Provide default route plugin manager configuration.
     *
     * @return array
     */
    public function getRouteManagerConfig()
    {
        return [
            'aliases' => [
                'chain'    => Chain::class,
                'Chain'    => Chain::class,
                'hostname' => Hostname::class,
                'Hostname' => Hostname::class,
                'literal'  => Literal::class,
                'Literal'  => Literal::class,
                'method'   => Method::class,
                'Method'   => Method::class,
                'part'     => Part::class,
                'Part'     => Part::class,
                'regex'    => Regex::class,
                'Regex'    => Regex::class,
                'scheme'   => Scheme::class,
                'Scheme'   => Scheme::class,
                'segment'  => Segment::class,
                'Segment'  => Segment::class,
                'Zend\Router\Http\Chain' => Chain::class,
                'Zend\Router\Http\Hostname' => Hostname::class,
                'Zend\Router\Http\Literal' => Literal::class,
                'Zend\Router\Http\Method' => Method::class,
                'Zend\Router\Http\Part' => Part::class,
                'Zend\Router\Http\Placeholder' => Placeholder::class,
                'Zend\Router\Http\Regex' => Regex::class,
                'Zend\Router\Http\Scheme' => Scheme::class,
                'Zend\Router\Http\Segment' => Segment::class,
            ],
            'factories' => [
                Chain::class    => RouteInvokableFactory::class,
                Hostname::class => RouteInvokableFactory::class,
                Literal::class  => RouteInvokableFactory::class,
                Method::class   => RouteInvokableFactory::class,
                Part::class     => RouteInvokableFactory::class,
                Placeholder::class => RouteInvokableFactory::class,
                Regex::class    => RouteInvokableFactory::class,
                Scheme::class   => RouteInvokableFactory::class,
                Segment::class  => RouteInvokableFactory::class,
            ],
        ];
    }
}
