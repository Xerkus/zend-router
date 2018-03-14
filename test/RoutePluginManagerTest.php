<?php
/**
 * @see       https://github.com/zendframework/zend-router for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-router/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Router;

use PHPUnit\Framework\TestCase;
use Zend\Router\RoutePluginManager;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\ServiceManager;

/**
 * @covers \Zend\Router\RoutePluginManager
 */
class RoutePluginManagerTest extends TestCase
{
    public function testLoadNonExistentRoute()
    {
        $routes = new RoutePluginManager(new ServiceManager());
        $this->expectException(ServiceNotFoundException::class);
        $routes->get('foo');
    }

    public function testCanLoadAnyRoute()
    {
        $routes = new RoutePluginManager(new ServiceManager(), [
            'aliases' => [
                'DummyRoute' => TestAsset\DummyRoute::class,
            ],
        ]);
        $route = $routes->get('DummyRoute');

        $this->assertInstanceOf(TestAsset\DummyRoute::class, $route);
    }
}
