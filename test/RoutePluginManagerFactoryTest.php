<?php
/**
 *  @see       https://github.com/zendframework/zend-router for the canonical source repository
 *  @copyright Copyright (c) 2015-2018 Zend Technologies USA Inc. (https://www.zend.com)
 *  @license   https://github.com/zendframework/zend-router/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Router;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Container\ContainerInterface;
use Zend\Router\RouteInterface;
use Zend\Router\RoutePluginManager;
use Zend\Router\RoutePluginManagerFactory;

/**
 * @covers \Zend\Router\RoutePluginManagerFactory
 */
class RoutePluginManagerFactoryTest extends TestCase
{
    public function testContainerInjectedIntoPluginManager()
    {
        $container = $this->prophesize(ContainerInterface::class);
        $container->has(Argument::any())
            ->willReturn(false);
        $container->has('called')
            ->shouldBeCalled();

        $factory = new RoutePluginManagerFactory();

        $plugins = $factory->__invoke($container->reveal());

        $plugins->setFactory('test container', function (ContainerInterface $container) {
            $container->has('called');
            return $this->prophesize(RouteInterface::class)->reveal();
        });

        $plugins->get('test container');
    }

    public function testInjectsRouteManagerConfigWhenAvailable()
    {
        $config[RoutePluginManager::class] = [
            'factories' => [
                'test route' => function () {
                    return $this->prophesize(RouteInterface::class)->reveal();
                },
            ],
        ];
        $container = $this->prophesize(ContainerInterface::class);
        $container->has('config')
            ->willReturn(true);
        $container->get('config')
            ->willReturn($config)
            ->shouldBeCalled();

        $factory = new RoutePluginManagerFactory();

        $plugins = $factory->__invoke($container->reveal());

        $this->assertTrue($plugins->has('test route'));
        $route = $plugins->get('test route');
        $this->assertInstanceOf(RouteInterface::class, $route);
    }

    public function testNoErrorIfNoRouteManagerConfigAvailable()
    {
        $this->setUseErrorHandler(true);
        $container = $this->prophesize(ContainerInterface::class);
        $container->has('config')
            ->willReturn(true);
        $container->get('config')
            ->willReturn([])
            ->shouldBeCalled();

        $factory = new RoutePluginManagerFactory();

        $factory->__invoke($container->reveal());

        // depends on php warnings and notices to be converted to exceptions
        $this->addToAssertionCount(1);
    }
}
