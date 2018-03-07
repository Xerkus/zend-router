<?php
/**
 *  @see       https://github.com/zendframework/zend-router for the canonical source repository
 *  @copyright Copyright (c) 2015-2018 Zend Technologies USA Inc. (https://www.zend.com)
 *  @license   https://github.com/zendframework/zend-router/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Router\Route\Partial;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Uri;
use Zend\Router\Exception\DomainException;
use Zend\Router\Route\Partial\Terminable;
use Zend\Router\RouteInterface;
use Zend\Router\RouteResult;

/**
 * @covers \Zend\Router\Route\Partial\Terminable
 */
class TerminableTest extends TestCase
{
    public function testSingleton()
    {
        $this->assertSame(
            Terminable::getInstance(),
            Terminable::getInstance()
        );
    }

    public function testMatchPartialReturnsRouteSuccessOnFullPathMatch()
    {
        $request = new ServerRequest([], [], '/foo');
        /** @var RouteInterface|MockObject $next */
        $next = $this->getMockBuilder(RouteInterface::class)
            ->getMock();
        $next->expects($this->never())
            ->method('match');

        $route = Terminable::getInstance();
        $result = $route->matchPartial($request, $next, 4);
        $this->assertTrue($result->isSuccess());
    }

    public function testMatchPartialDelegatesToNextOnPartialMatch()
    {
        $request = new ServerRequest([], [], '/foo');
        $expected = RouteResult::fromRouteMatch([]);
        /** @var RouteInterface|MockObject $next */
        $next = $this->getMockBuilder(RouteInterface::class)
            ->getMock();
        $next->expects($this->once())
            ->method('match')
            ->with($request, 3, ['foo' => 'bar'])
            ->willReturn($expected);

        $route = Terminable::getInstance();
        $result = $route->matchPartial($request, $next, 3, ['foo' => 'bar']);
        $this->assertSame($expected, $result);
    }

    public function testAssemblePartialReturnsUriIfNoNameOptionPresent()
    {
        $expected = new Uri();
        /** @var RouteInterface|MockObject $next */
        $next = $this->getMockBuilder(RouteInterface::class)
            ->getMock();
        $next->expects($this->never())
            ->method('assemble');

        $route = Terminable::getInstance();
        $uri = $route->assemblePartial($expected, $next, ['foo' => 'bar'], ['baz' => 'qux']);
        $this->assertSame($expected, $uri);
    }

    public function testAssemblePartialReturnsUriIfNoNameOptionEmpty()
    {
        $expected = new Uri();
        /** @var RouteInterface|MockObject $next */
        $next = $this->getMockBuilder(RouteInterface::class)
            ->getMock();
        $next->expects($this->never())
            ->method('assemble');

        $route = Terminable::getInstance();
        $uri = $route->assemblePartial($expected, $next, ['foo' => 'bar'], ['baz' => 'qux', 'name' => '']);
        $this->assertSame($expected, $uri);
    }

    public function testAssemblePartialDelegatesToNextIfNameOptionPresent()
    {
        $expected = new Uri();
        $uri = new Uri();
        /** @var RouteInterface|MockObject $next */
        $next = $this->getMockBuilder(RouteInterface::class)
            ->getMock();
        $next->expects($this->once())
            ->method('assemble')
            ->with($uri, ['foo' => 'bar'], ['baz' => 'qux', 'name' => 'child'])
            ->willReturn($expected);

        $route = Terminable::getInstance();
        $returned = $route->assemblePartial($uri, $next, ['foo' => 'bar'], ['baz' => 'qux', 'name' => 'child']);
        $this->assertSame($expected, $returned);
    }

    public function testMatchShouldNotBeUsedDirectly()
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('not be used');

        $route = Terminable::getInstance();

        $route->match(new ServerRequest());
    }

    public function testAssembleShouldNotBeUsedDirectly()
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('not be used');

        $route = Terminable::getInstance();

        $route->assemble(new Uri());
    }
}
