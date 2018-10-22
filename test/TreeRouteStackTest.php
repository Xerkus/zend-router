<?php
/**
 * @link      http://github.com/zendframework/zend-router for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Router;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Uri;
use Zend\Router\Exception\InvalidArgumentException;
use Zend\Router\Exception\RuntimeException;
use Zend\Router\RouteInterface;
use Zend\Router\RouteResult;
use Zend\Router\RouteStackInterface;
use Zend\Router\TreeRouteStack;

/**
 * @covers \Zend\Router\TreeRouteStack
 */
class TreeRouteStackTest extends TestCase
{
    /** @var TreeRouteStack */
    private $stack;

    public function setUp() : void
    {
        $this->stack = new TreeRouteStack();
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

    public function testMatchPrependsMatchedRouteNameToNameInResultFromRoute()
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
        $this->assertSame('foo/baz', $result->getMatchedRouteName());
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
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing "name" option');
        $this->stack->assemble(new Uri());
    }

    public function testAssembleNonExistentRoute()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Route with name "foo" not found');
        $this->stack->assemble(new Uri(), [], ['name' => 'foo']);
    }

    public function testAssembleNonExistentChildRoute()
    {
        $route = $this->prophesize(RouteInterface::class);
        $route->assemble()->shouldNotBeCalled();

        $stack = new TreeRouteStack();
        $stack->addRoute(
            'index',
            $route->reveal()
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Route with name "index" does not have child routes');
        $stack->assemble(new Uri(), [], ['name' => 'index/foo']);
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

    public function testStripsFirstRouteNameSegmentFromOptionsForAssembling()
    {
        $uri = new Uri();
        $route = $this->prophesize(RouteStackInterface::class);
        $route->assemble($uri, [], ['name' => 'bar/baz'])
            ->willReturn($uri)
            ->shouldBeCalled();

        $this->stack->addRoute('foo', $route->reveal());

        $this->stack->assemble($uri, [], ['name' => 'foo/bar/baz']);
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
