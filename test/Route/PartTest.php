<?php
/**
 * @link      http://github.com/zendframework/zend-router for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Router\Route;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Uri;
use Zend\Router\Exception\InvalidArgumentException;
use Zend\Router\Exception\RuntimeException;
use Zend\Router\Route\Literal;
use Zend\Router\Route\Method;
use Zend\Router\Route\Part;
use Zend\Router\Route\PartialRouteInterface;
use Zend\Router\Route\Segment;
use Zend\Router\RouteResult;
use Zend\Router\TreeRouteStack;
use ZendTest\Router\Route\TestAsset\RouteTestDefinition;
use ZendTest\Router\Route\TestAsset\RouteTestTrait;

/**
 * @covers \Zend\Router\Route\Part
 */
class PartTest extends TestCase
{
    use RouteTestTrait;

    public function getTestRoute() : Part
    {
        return new Part(
            new Literal('/foo', ['controller' => 'foo']),
            [],
            [
                'bar' => new Literal('/bar', ['controller' => 'bar']),
                'baz' => new Part(
                    new Literal('/baz'),
                    [],
                    [
                        'bat' => new Segment('/:controller'),
                    ]
                ),
                'bat' => new Part(
                    new Segment('/bat[/:foo]', [], ['foo' => 'bar']),
                    [],
                    [
                        'literal' => new Literal('/bar'),
                        'optional' => new Segment('/bat[/:bar]'),
                    ],
                    true
                ),
            ],
            true
        );
    }

    public function getRouteAlternative() : Part
    {
        return new Part(
            new Segment('/[:controller[/:action]]', [], [
                'controller' => 'fo-fo',
                'action' => 'index',
            ]),
            [],
            [],
            true
        );
    }

    public function getRouteTestDefinitions() : iterable
    {
        $params = ['controller' => 'foo'];
        yield 'simple match' => (new RouteTestDefinition(
            $this->getTestRoute(),
            new Uri('/foo')
        ))
            ->expectMatchResult(
                RouteResult::fromRouteMatch($params)
            )
            ->shouldAssembleAndExpectResultSameAsUriForMatching()
            ->useParamsForAssemble($params);

        $params = ['controller' => 'foo'];
        yield 'offset-skips-beginning' => (new RouteTestDefinition(
            $this->getTestRoute(),
            new Uri('/bar/foo')
        ))
            ->usePathOffset(4)
            ->expectMatchResult(
                RouteResult::fromRouteMatch($params)
            )
            ->shouldAssembleAndExpectResult(new Uri('/foo'))
            ->useParamsForAssemble($params);

        $params = ['controller' => 'bar'];
        yield 'simple child match' => (new RouteTestDefinition(
            $this->getTestRoute(),
            new Uri('/foo/bar')
        ))
            ->expectMatchResult(
                RouteResult::fromRouteMatch($params, 'bar')
            )
            ->shouldAssembleAndExpectResultSameAsUriForMatching()
            ->useParamsForAssemble($params)
            ->useOptionsForAssemble(['name' => 'bar']);

        yield 'non terminating part does not match' => (new RouteTestDefinition(
            $this->getTestRoute(),
            new Uri('/foo/baz')
        ))
            ->expectMatchResult(
                RouteResult::fromRouteFailure()
            );

        $params = ['controller' => 'bat'];
        yield 'child of non terminating part does match' => (new RouteTestDefinition(
            $this->getTestRoute(),
            new Uri('/foo/baz/bat')
        ))
            ->expectMatchResult(
                RouteResult::fromRouteMatch($params, 'baz/bat')
            )
            ->shouldAssembleAndExpectResultSameAsUriForMatching()
            ->useParamsForAssemble($params)
            ->useOptionsForAssemble(['name' => 'baz/bat']);

        $params = ['controller' => 'foo', 'foo' => 'bar'];
        yield 'optional parameters are dropped without child' => (new RouteTestDefinition(
            $this->getTestRoute(),
            new Uri('/foo/bat')
        ))
            ->expectMatchResult(
                RouteResult::fromRouteMatch($params, 'bat')
            )
            ->shouldAssembleAndExpectResultSameAsUriForMatching()
            ->useParamsForAssemble($params)
            ->useOptionsForAssemble(['name' => 'bat']);

        $params = ['controller' => 'foo', 'foo' => 'bar'];
        yield 'optional parameters are not dropped with child' => (new RouteTestDefinition(
            $this->getTestRoute(),
            new Uri('/foo/bat/bar/bar')
        ))
            ->expectMatchResult(
                RouteResult::fromRouteMatch($params, 'bat/literal')
            )
            ->shouldAssembleAndExpectResultSameAsUriForMatching()
            ->useParamsForAssemble($params)
            ->useOptionsForAssemble(['name' => 'bat/literal']);

        $params = ['controller' => 'foo', 'foo' => 'bar'];
        yield 'optional parameters not required in last part' => (new RouteTestDefinition(
            $this->getTestRoute(),
            new Uri('/foo/bat/bar/bat')
        ))
            ->expectMatchResult(
                RouteResult::fromRouteMatch($params, 'bat/optional')
            )
            ->shouldAssembleAndExpectResultSameAsUriForMatching()
            ->useParamsForAssemble($params)
            ->useOptionsForAssemble(['name' => 'bat/optional']);

        $params = ['controller' => 'fo-fo', 'action' => 'index'];
        yield 'simple match 2' => (new RouteTestDefinition(
            $this->getRouteAlternative(),
            new Uri('/')
        ))
            ->expectMatchResult(
                RouteResult::fromRouteMatch($params)
            )
            ->shouldAssembleAndExpectResultSameAsUriForMatching()
            ->useParamsForAssemble($params);
    }

    public function testAssembleNonTerminatedRoute()
    {
        $uri = new Uri();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Part route may not terminate');
        $this->getTestRoute()->assemble($uri, [], ['name' => 'baz']);
    }

    public function testMethodFailureReturnsMethodFailureOnTerminatedMatch()
    {
        $route = new Part(new Method('GET,POST'), [], [], true);

        $request = new ServerRequest([], [], new Uri('/foo'), 'PUT');
        $result = $route->match($request, 4);
        $this->assertTrue($result->isMethodFailure());
        $this->assertArraySubset(['GET', 'POST'], $result->getAllowedMethods());
        $this->assertCount(2, $result->getAllowedMethods());
    }

    public function testMethodFailureReturnsMethodFailureOnFullPathMatch()
    {
        $route = new Part(
            new Method('GET,POST'),
            [],
            [
                'foo' => new Literal('/foo'),
            ],
            true
        );

        $request = new ServerRequest([], [], new Uri('/foo'), 'PUT');
        $result = $route->match($request, 0);
        $this->assertTrue($result->isMethodFailure());
        $this->assertArraySubset(['GET', 'POST'], $result->getAllowedMethods());
        $this->assertCount(2, $result->getAllowedMethods());
    }

    public function testMethodFailureReturnsFailureIfChildRoutesFail()
    {
        $route = new Part(
            new Method('GET,POST'),
            [],
            [
                'foo' => new Literal('/foo'),
            ],
            true
        );

        $request = new ServerRequest([], [], new Uri('/bar'), 'PUT');
        $result = $route->match($request, 0);
        $this->assertTrue($result->isFailure());
        $this->assertFalse($result->isMethodFailure());
    }

    public function testMethodFailureReturnsMethodIntersectionBetweenPartialAndChildRoutes()
    {
        $route = new Part(
            new Method('GET,POST'),
            [],
            [
                'foo' => new Part(
                    new Literal('/foo'),
                    [],
                    [
                        'verb' => new Method('POST,DELETE'),
                    ]
                ),
            ],
            true
        );

        $request = new ServerRequest([], [], new Uri('/foo'), 'PUT');
        $result = $route->match($request, 0);
        $this->assertTrue($result->isMethodFailure());
        $this->assertEquals(['POST'], $result->getAllowedMethods());
    }

    public function testMethodFailureWithChildMethodsNotIntersectingIsAFailure()
    {
        $route = new Part(
            new Method('GET,POST'),
            [],
            [
                'foo' => new Part(
                    new Literal('/foo'),
                    [],
                    [
                        'verb' => new Method('PUT,DELETE'),
                    ]
                ),
            ],
            true
        );

        $request = new ServerRequest([], [], new Uri('/foo'), 'PUT');
        $result = $route->match($request, 0);
        $this->assertTrue($result->isFailure());
        $this->assertFalse($result->isMethodFailure());
    }

    public function testChildMethodFailureWithParentPartSuccessReturnsMethodIntersection()
    {
        $route = new Part(
            new Method('GET,POST,DELETE'),
            [],
            [
                'foo' => new Part(
                    new Literal('/foo'),
                    [],
                    [
                        'verb' => new Method('POST, PUT,DELETE'),
                    ]
                ),
            ],
            true
        );

        $request = new ServerRequest([], [], new Uri('/foo'), 'GET');
        $result = $route->match($request, 0);
        $this->assertTrue($result->isMethodFailure());
        $this->assertEquals(['POST', 'DELETE'], $result->getAllowedMethods());
    }

    public function testParentMethodFailureWithChildSuccessReturnsFullListOfMethods()
    {
        $route = new Part(
            new Method('GET,POST,DELETE'),
            [],
            [
                'foo' => new Part(
                    new Literal('/foo'),
                    [],
                    [
                        'verb' => new Method('DELETE,OPTIONS'),
                    ]
                ),
            ],
            true
        );

        $request = new ServerRequest([], [], new Uri('/foo'), 'OPTIONS');
        $result = $route->match($request, 0);
        $this->assertTrue($result->isMethodFailure());
        $this->assertEquals(['DELETE'], $result->getAllowedMethods());
    }

    /**
     * @group 3711
     */
    public function testPartRouteMarkedAsMayTerminateCanMatchWhenQueryStringPresent()
    {
        $route = new Part(
            new Literal('/resource', ['controller' => 'ResourceController', 'action' => 'resource']),
            [],
            [
                'child' => new Literal('/child'),
            ],
            true
        );

        $request = new ServerRequest([], [], new Uri('http://example.com/resource?foo=bar'));
        $request = $request->withQueryParams(['foo' => 'bar']);

        $result = $route->match($request);
        $this->assertTrue($result->isSuccess());
        $this->assertEquals('resource', $result->getMatchedParams()['action']);
    }

    public function testRejectsNegativePathOffset()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Path offset cannot be negative');
        $partial = $this->prophesize(PartialRouteInterface::class);
        $request = $this->prophesize(ServerRequestInterface::class);
        $route = new Part($partial->reveal(), [], new TreeRouteStack(), false);
        $route->match($request->reveal(), -1);
    }
}
