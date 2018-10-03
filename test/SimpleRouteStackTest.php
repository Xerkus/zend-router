<?php
/**
 * @link      http://github.com/zendframework/zend-router for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Router;

use ArrayIterator;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use TypeError;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Uri;
use Zend\Router\Exception\InvalidArgumentException;
use Zend\Router\Exception\RuntimeException;
use Zend\Router\Http\Literal;
use Zend\Router\RouteInterface;
use Zend\Router\RouteResult;
use Zend\Router\SimpleRouteStack;

/**
 * Note: route stack is LIFO
 *
 * @covers \Zend\Router\SimpleRouteStack
 */
class SimpleRouteStackTest extends TestCase
{
    /** @var SimpleRouteStack */
    private $stack;

    public function setUp() : void
    {
        $this->stack = new SimpleRouteStack();
    }

    public function testHaveNoRoutesByDefault()
    {
        $this->assertEmpty($this->stack->getRoutes());
    }

    public function testCanAddAndGetBackRouteByName()
    {
        /** @var RouteInterface $fooRoute */
        $fooRoute = $this->prophesize(RouteInterface::class)->reveal();

        $this->stack->addRoute('foo', $fooRoute);
        $this->assertSame($fooRoute, $this->stack->getRoute('foo'));
    }

    public function testGettingNonExistentRouteReturnsNull()
    {
        $this->assertNull($this->stack->getRoute('foo'));
    }

    public function testAddRoutesAsArray()
    {
        $fooRoute = $this->prophesize(RouteInterface::class)->reveal();
        $barRoute = $this->prophesize(RouteInterface::class)->reveal();

        $this->stack->addRoutes([
            'foo' => $fooRoute,
            'bar' => $barRoute,
        ]);

        $this->assertSame($fooRoute, $this->stack->getRoute('foo'));
        $this->assertSame($barRoute, $this->stack->getRoute('bar'));
        $this->assertSame(['bar' => $barRoute, 'foo' => $fooRoute], $this->stack->getRoutes());
    }

    public function testAddRoutesAsIterable()
    {
        $fooRoute = $this->prophesize(RouteInterface::class)->reveal();
        $barRoute = $this->prophesize(RouteInterface::class)->reveal();

        $this->stack->addRoutes(new ArrayIterator([
            'foo' => $fooRoute,
            'bar' => $barRoute,
        ]));

        $this->assertSame($fooRoute, $this->stack->getRoute('foo'));
        $this->assertSame($barRoute, $this->stack->getRoute('bar'));
        $this->assertSame(['bar' => $barRoute, 'foo' => $fooRoute], $this->stack->getRoutes());
    }

    public function testAddRoutesOverwritesExistingRouteByName()
    {
        $fooRoute = $this->prophesize(RouteInterface::class)->reveal();
        $barRoute = $this->prophesize(RouteInterface::class)->reveal();
        $barRoute2 = $this->prophesize(RouteInterface::class)->reveal();

        $this->stack->addRoutes([
            'foo' => $fooRoute,
            'bar' => $barRoute,
        ]);

        $this->stack->addRoutes(['bar' => $barRoute2]);

        $this->assertSame($fooRoute, $this->stack->getRoute('foo'));
        $this->assertSame($barRoute2, $this->stack->getRoute('bar'));
        $this->assertSame(['bar' => $barRoute2, 'foo' => $fooRoute], $this->stack->getRoutes());
    }

    public function testAddRoutesWithInvalidRouteCausesError()
    {
        $this->expectException(TypeError::class);
        $this->stack->addRoutes([
            'foo' => [
                'type'    => Literal::class,
                'options' => [],
            ],
        ]);
    }

    public function testSetRoutesAsArray()
    {
        $fooRoute = $this->prophesize(RouteInterface::class)->reveal();
        $barRoute = $this->prophesize(RouteInterface::class)->reveal();

        $this->stack->setRoutes([
            'foo' => $fooRoute,
            'bar' => $barRoute,
        ]);

        $this->assertSame($fooRoute, $this->stack->getRoute('foo'));
        $this->assertSame($barRoute, $this->stack->getRoute('bar'));
        $this->assertSame(['bar' => $barRoute, 'foo' => $fooRoute], $this->stack->getRoutes());
    }

    public function testSetRoutesAsIterable()
    {
        $fooRoute = $this->prophesize(RouteInterface::class)->reveal();
        $barRoute = $this->prophesize(RouteInterface::class)->reveal();

        $this->stack->setRoutes(new ArrayIterator([
            'foo' => $fooRoute,
            'bar' => $barRoute,
        ]));

        $this->assertSame($fooRoute, $this->stack->getRoute('foo'));
        $this->assertSame($barRoute, $this->stack->getRoute('bar'));
        $this->assertSame(['bar' => $barRoute, 'foo' => $fooRoute], $this->stack->getRoutes());
    }

    public function testSetRoutesOverwritesExistingRoutes()
    {
        $fooRoute = $this->prophesize(RouteInterface::class)->reveal();
        $fooRoute2 = $this->prophesize(RouteInterface::class)->reveal();
        $barRoute = $this->prophesize(RouteInterface::class)->reveal();

        $this->stack->setRoutes([
            'foo' => $fooRoute,
            'bar' => $barRoute,
        ]);

        $this->stack->setRoutes(['foo' => $fooRoute2]);

        $this->assertSame($fooRoute2, $this->stack->getRoute('foo'));
        $this->assertNull($this->stack->getRoute('bar'));
        $this->assertSame(['foo' => $fooRoute2], $this->stack->getRoutes());
    }

    public function testSetEmptyRoutesRemovesAllRoutes()
    {
        $fooRoute = $this->prophesize(RouteInterface::class)->reveal();
        $barRoute = $this->prophesize(RouteInterface::class)->reveal();

        $this->stack->setRoutes([
            'foo' => $fooRoute,
            'bar' => $barRoute,
        ]);

        $this->stack->setRoutes([]);

        $this->assertEmpty($this->stack->getRoutes());
    }

    public function testSetRoutesWithInvalidRouteCausesError()
    {
        $this->expectException(TypeError::class);
        $this->stack->setRoutes([
            'foo' => [
                'type'    => Literal::class,
                'options' => [],
            ],
        ]);
    }

    public function testAddRouteWithPriority()
    {
        /** @var RouteInterface $fooRoute */
        $fooRoute = $this->prophesize(RouteInterface::class)->reveal();
        /** @var RouteInterface $barRoute */
        $barRoute = $this->prophesize(RouteInterface::class)->reveal();
        $this->stack->addRoute('foo', $fooRoute, 2);
        $this->stack->addRoute('bar', $barRoute, 1);

        $this->assertSame(['foo' => $fooRoute, 'bar' => $barRoute], $this->stack->getRoutes());
    }

    public function testRemoveRouteByName()
    {
        $fooRoute = $this->prophesize(RouteInterface::class)->reveal();
        $barRoute = $this->prophesize(RouteInterface::class)->reveal();

        $this->stack->addRoutes([
            'foo' => $fooRoute,
            'bar' => $barRoute,
        ]);

        $this->stack->removeRoute('foo');

        $this->assertNull($this->stack->getRoute('foo'));
        $this->assertSame($barRoute, $this->stack->getRoute('bar'));
        $this->assertSame(['bar' => $barRoute], $this->stack->getRoutes());
    }

    public function testRemoveNonExistentRouteByNameIsANoop()
    {
        $fooRoute = $this->prophesize(RouteInterface::class)->reveal();

        $this->stack->addRoute('foo', $fooRoute);

        $this->stack->removeRoute('baz');

        $this->assertSame($fooRoute, $this->stack->getRoute('foo'));
        $this->assertSame(['foo' => $fooRoute], $this->stack->getRoutes());
    }

    public function testMatchOnEmptyStackResultsInRoutingFailure()
    {
        $request = new ServerRequest();
        $result = $this->stack->match($request);

        $this->assertTrue($result->isFailure());
    }

    public function testMatchUsesFirstSuccessfulMatch()
    {
        $request = new ServerRequest();
        $fooRoute = $this->prophesize(RouteInterface::class);
        $fooRoute->match(Argument::cetera())
            ->shouldNotBeCalled();

        $barRoute = $this->prophesize(RouteInterface::class);
        $barRoute->match($request, 0, Argument::any())
            ->willReturn(RouteResult::fromRouteMatch(['matched' => 'bar']));

        $bazRoute = $this->prophesize(RouteInterface::class);
        $bazRoute->match($request, 0, Argument::any())
            ->willReturn(RouteResult::fromRouteFailure());

        $this->stack->addRoutes([
            'foo' => $fooRoute->reveal(),
            'bar' => $barRoute->reveal(),
            'baz' => $bazRoute->reveal(),
        ]);

        $result = $this->stack->match($request);

        $this->assertTrue($result->isSuccess());
        $this->assertSame('bar', $result->getMatchedRouteName());
        $this->assertSame(['matched' => 'bar'], $result->getMatchedParams());
    }

    public function testMatchPassesPathOffsetAndOptionsToRoutes()
    {
        $request = new ServerRequest();
        $fooRoute = $this->prophesize(RouteInterface::class);
        $fooRoute->match($request, 5, ['opt' => 'value'])
            ->willReturn(RouteResult::fromRouteFailure())
            ->shouldBeCalled();

        $this->stack->addRoute('foo', $fooRoute->reveal());

        $this->stack->match($request, 5, ['opt' => 'value']);
    }

    public function testMatchesInLifoOrder()
    {
        $callOrder = [];
        $fooRoute = $this->prophesize(RouteInterface::class);
        $fooRoute->match(Argument::cetera())
            ->will(function () use (&$callOrder) {
                $callOrder[] = 'foo';
                return RouteResult::fromRouteFailure();
            });
        $barRoute = $this->prophesize(RouteInterface::class);
        $barRoute->match(Argument::cetera())
            ->will(function () use (&$callOrder) {
                $callOrder[] = 'bar';
                return RouteResult::fromRouteFailure();
            });
        $bazRoute = $this->prophesize(RouteInterface::class);
        $bazRoute->match(Argument::cetera())
            ->will(function () use (&$callOrder) {
                $callOrder[] = 'baz';
                return RouteResult::fromRouteFailure();
            });
        $quxRoute = $this->prophesize(RouteInterface::class);
        $quxRoute->match(Argument::cetera())
            ->will(function () use (&$callOrder) {
                $callOrder[] = 'qux';
                return RouteResult::fromRouteFailure();
            });
        $this->stack->addRoute('foo', $fooRoute->reveal());
        $this->stack->addRoutes(['bar' => $barRoute->reveal(), 'baz' => $bazRoute->reveal()]);
        $this->stack->addRoute('qux', $quxRoute->reveal());
        $request = new ServerRequest();

        $this->stack->match($request);

        $this->assertSame(['qux', 'baz', 'bar', 'foo'], $callOrder);
    }

    public function testMatchRespectsExplicitPriority()
    {
        $callOrder = [];
        $fooRoute = $this->prophesize(RouteInterface::class);
        $fooRoute->match(Argument::cetera())
            ->will(function () use (&$callOrder) {
                $callOrder[] = 'foo';
                return RouteResult::fromRouteFailure();
            });
        $barRoute = $this->prophesize(RouteInterface::class);
        $barRoute->match(Argument::cetera())
            ->will(function () use (&$callOrder) {
                $callOrder[] = 'bar';
                return RouteResult::fromRouteFailure();
            });
        $bazRoute = $this->prophesize(RouteInterface::class);
        $bazRoute->match(Argument::cetera())
            ->will(function () use (&$callOrder) {
                $callOrder[] = 'baz';
                return RouteResult::fromRouteFailure();
            });
        $quxRoute = $this->prophesize(RouteInterface::class);
        $quxRoute->match(Argument::cetera())
            ->will(function () use (&$callOrder) {
                $callOrder[] = 'qux';
                return RouteResult::fromRouteFailure();
            });
        $this->stack->addRoute('foo', $fooRoute->reveal(), 2);
        $this->stack->addRoutes(['bar' => $barRoute->reveal(), 'baz' => $bazRoute->reveal()]);
        $this->stack->addRoute('qux', $quxRoute->reveal(), 1);
        $request = new ServerRequest();

        $this->stack->match($request);

        $this->assertSame(['foo', 'qux', 'baz', 'bar'], $callOrder);
    }

    public function testMatchReplacesMatchedRouteNameInResultFromRoute()
    {
        $fooRoute = $this->prophesize(RouteInterface::class);
        $fooRoute->match(Argument::cetera())
            ->willReturn(RouteResult::fromRouteMatch(
                [],
                'baz'
            ));

        $this->stack->addRoute('foo', $fooRoute->reveal());

        $request = new ServerRequest();
        $result = $this->stack->match($request);

        $this->assertTrue($result->isSuccess());
        $this->assertSame('foo', $result->getMatchedRouteName());
    }

    public function testMatchedParametersIncludeDefaults()
    {
        $fooRoute = $this->prophesize(RouteInterface::class);
        $fooRoute->match(Argument::cetera())
            ->willReturn(RouteResult::fromRouteMatch(['matched' => 'value']));

        $this->stack->setDefaultParams(['default_param' => 'value']);
        $this->stack->setDefaultParam('another_default_param', 'value');

        $this->stack->addRoute('foo', $fooRoute->reveal());

        $request = new ServerRequest();
        $result = $this->stack->match($request);

        $this->assertEquals([
            'matched' => 'value',
            'default_param' => 'value',
            'another_default_param' => 'value',
        ], $result->getMatchedParams());
    }

    public function testDefaultsDoNotOverrideMatchedParams()
    {
        $fooRoute = $this->prophesize(RouteInterface::class);
        $fooRoute->match(Argument::cetera())
            ->willReturn(RouteResult::fromRouteMatch(['matched' => 'value']));

        $this->stack->setDefaultParams(['matched' => 'other value', 'default_param' => 'value']);

        $this->stack->addRoute('foo', $fooRoute->reveal());

        $request = new ServerRequest();
        $result = $this->stack->match($request);

        $this->assertEquals(['matched' => 'value', 'default_param' => 'value'], $result->getMatchedParams());
    }

    public function testAssembleWithoutNameOption()
    {
        $stack = new SimpleRouteStack();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing "name" option');
        $stack->assemble(new Uri());
    }

    public function testAssembleNonExistentRoute()
    {
        $stack = new SimpleRouteStack();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Route with name "foo" not found');
        $stack->assemble(new Uri(), [], ['name' => 'foo']);
    }

    public function testAssembleReturnsUriFromRoute()
    {
        $uri = new Uri();
        $expectedUri = new Uri();
        $route = $this->prophesize(RouteInterface::class);
        $route->assemble($uri, [], [])
            ->willReturn($expectedUri)
            ->shouldBeCalled();

        $this->stack->addRoute('foo', $route->reveal());

        $returned = $this->stack->assemble($uri, [], ['name' => 'foo']);

        $this->assertSame($expectedUri, $returned);
    }

    public function testStripsNameOptionFromOptionsForAssembling()
    {
        $uri = new Uri();
        $route = $this->prophesize(RouteInterface::class);
        $route->assemble($uri, [], [])
            ->willReturn($uri)
            ->shouldBeCalled();

        $this->stack->addRoute('foo', $route->reveal());

        $this->stack->assemble($uri, [], ['name' => 'foo']);
    }

    public function testUriAndSubstitutionsAndOptionsPassedToRouteForAssembling()
    {
        $uri = new Uri();
        $route = $this->prophesize(RouteInterface::class);
        $route->assemble($uri, ['substitution' => 'passed'], ['opt' => 'passed'])
            ->willReturn($uri)
            ->shouldBeCalled();

        $this->stack->addRoute('foo', $route->reveal());

        $this->stack->assemble($uri, ['substitution' => 'passed'], ['name' => 'foo', 'opt' => 'passed']);
    }

    public function testDefaultParametersAddedToSubstitutionParametersForAssembling()
    {
        $this->stack->setDefaultParams(['default' => 'value']);
        $uri = new Uri();
        $route = $this->prophesize(RouteInterface::class);
        $route->assemble($uri, ['substitution' => 'passed', 'default' => 'value'], [])
            ->willReturn($uri)
            ->shouldBeCalled();

        $this->stack->addRoute('foo', $route->reveal());

        $this->stack->assemble($uri, ['substitution' => 'passed'], ['name' => 'foo']);
    }

    public function testDefaultsDoNotOverrideSubstitutionParametersForAssembling()
    {
        $this->stack->setDefaultParams(['substitution' => 'default', 'default' => 'value']);
        $uri = new Uri();
        $route = $this->prophesize(RouteInterface::class);
        $route->assemble($uri, ['substitution' => 'passed', 'default' => 'value'], [])
            ->willReturn($uri)
            ->shouldBeCalled();

        $this->stack->addRoute('foo', $route->reveal());

        $this->stack->assemble($uri, ['substitution' => 'passed', 'default' => 'value'], ['name' => 'foo']);
    }
}
