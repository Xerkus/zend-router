<?php
/**
 * @link      http://github.com/zendframework/zend-router for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Router\Route;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Uri;
use Zend\Router\Exception\InvalidArgumentException;
use Zend\Router\Route\Literal;
use Zend\Router\Route\Partial\FullMatch;
use Zend\Router\RouteInterface;
use Zend\Router\RouteResult;
use ZendTest\Router\Route\TestAsset\PartialRouteTestTrait;
use ZendTest\Router\Route\TestAsset\RouteTestDefinition;

/**
 * @covers \Zend\Router\Route\Literal
 */
class LiteralTest extends TestCase
{
    use PartialRouteTestTrait;

    public function getRouteTestDefinitions() : iterable
    {
        yield 'simple match' => (new RouteTestDefinition(
            new Literal('/foo'),
            new Uri('/foo')
        ))
            ->expectMatchResult(
                RouteResult::fromRouteMatch([])
            )
            ->expectPartialMatchResult(
                RouteResult::fromRouteMatch([])
            )
            ->shouldAssembleAndExpectResultSameAsUriForMatching();

        yield 'no match without leading slash' => (new RouteTestDefinition(
            new Literal('foo'),
            new Uri('/foo')
        ))
            ->expectMatchResult(
                RouteResult::fromRouteFailure()
            )
            ->expectPartialMatchResult(
                RouteResult::fromRouteFailure()
            );

        yield 'only partial match with trailing slash' => (new RouteTestDefinition(
            new Literal('/foo'),
            new Uri('/foo/')
        ))
            ->expectMatchResult(
                RouteResult::fromRouteFailure()
            )
            ->expectPartialMatchResult(
                RouteResult::fromRouteMatch([])
            );
        yield 'offset skips beginning' => (new RouteTestDefinition(
            new Literal('foo'),
            new Uri('/foo')
        ))
            ->usePathOffset(1)
            ->expectMatchResult(
                RouteResult::fromRouteMatch([])
            )
            ->expectPartialMatchResult(
                RouteResult::fromRouteMatch([])
            );
        yield 'offset does not prevent partial match' => (new RouteTestDefinition(
            new Literal('foo'),
            new Uri('/foo/bar')
        ))
            ->usePathOffset(1)
            ->expectMatchResult(
                RouteResult::fromRouteFailure()
            )
            ->expectPartialMatchResult(
                RouteResult::fromRouteMatch([])
            );
        yield 'assemble appends to path present in provided uri' => (new RouteTestDefinition(
            new Literal('/foo'),
            new Uri('/foo')
        ))
            ->expectMatchResult(
                RouteResult::fromRouteMatch([])
            )
            ->expectPartialMatchResult(
                RouteResult::fromRouteMatch([])
            )
            ->useUriForAssemble(new Uri('/bar'))
            ->shouldAssembleAndExpectResult(new Uri('/bar/foo'));
    }

    public function testAssemblePassesProvidedParametersToNextRoute()
    {
        $route = new Literal('/bar');
        /** @var RouteInterface|MockObject $next */
        $next = $this->getMockBuilder(RouteInterface::class)
            ->getMock();
        $next->expects($this->once())
            ->method('assemble')
            ->with($this->anything(), ['foo' => 'bar'], ['baz' => 'qux'])
            ->willReturn(new Uri());

        $route->assemblePartial(new Uri(), $next, ['foo' => 'bar'], ['baz' => 'qux']);
    }

    public function testAssemblePassesUriToNextRouteAndReturnsResult()
    {
        $uri = new Uri('/foo');
        $route = new Literal('/bar');
        $next = new Literal('/baz');

        $result = $route->assemblePartial($uri, $next);
        $this->assertSame('/foo/bar/baz', $result->getPath());
    }

    public function testEmptyLiteral()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Literal uri path part cannot be empty');
        new Literal('');
    }

    public function testRejectsNegativePathOffset()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Path offset cannot be negative');
        $request = $this->prophesize(ServerRequestInterface::class);
        $route = new Literal('/foo');
        $route->matchPartial($request->reveal(), FullMatch::getInstance(), -1);
    }
}
