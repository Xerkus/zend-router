<?php
/**
 * @link      http://github.com/zendframework/zend-router for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Router;

use PHPUnit\Framework\TestCase;
use Zend\Router\Route\HttpRouterFactory;
use Zend\Router\RoutePluginManager;
use Zend\Router\RouterFactory;
use Zend\ServiceManager\Config;
use Zend\ServiceManager\ServiceManager;

use function array_merge_recursive;

/**
 * @covers \Zend\Router\RouterFactory
 */
class RouterFactoryTest extends TestCase
{
    public function setUp()
    {
        $this->defaultServiceConfig = [
            'factories' => [
                'HttpRouter'         => HttpRouterFactory::class,
                'RoutePluginManager' => function ($services) {
                    return new RoutePluginManager($services);
                },
            ],
        ];

        $this->factory = new RouterFactory();
    }

    public function testFactoryCanCreateRouterBasedOnConfiguredName()
    {
        $config = new Config(array_merge_recursive($this->defaultServiceConfig, [
            'services' => [
                'config' => [
                    'router' => [
                        'router_class' => TestAsset\Router::class,
                    ],
                ],
            ],
        ]));
        $services = new ServiceManager();
        $config->configureServiceManager($services);

        $router = $this->factory->__invoke($services, 'router');
        $this->assertInstanceOf(TestAsset\Router::class, $router);
    }

    public function testFactoryCanCreateRouterWhenOnlyHttpRouterConfigPresent()
    {
        $config = new Config(array_merge_recursive($this->defaultServiceConfig, [
            'services' => [
                'config' => [
                    'router' => [
                        'router_class' => TestAsset\Router::class,
                    ],
                ],
            ],
        ]));
        $services = new ServiceManager();
        $config->configureServiceManager($services);

        $router = $this->factory->__invoke($services, 'router');
        $this->assertInstanceOf(TestAsset\Router::class, $router);
    }
}
