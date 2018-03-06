<?php
/**
 * @link      http://github.com/zendframework/zend-router for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Router\Route;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Uri;
use Zend\Router\Exception\InvalidArgumentException;
use Zend\Router\Route\Method;
use Zend\Router\Route\Partial\FullMatch;
use Zend\Router\RouteInterface;
use Zend\Router\RouteResult;
use ZendTest\Router\Route\TestAsset\PartialRouteTestTrait;
use ZendTest\Router\Route\TestAsset\RouteTestDefinition;

/**
 * @covers \Zend\Router\Route\Method
 */
class MethodTest extends TestCase
{
    use PartialRouteTestTrait;

    public function getRouteTestDefinitions() : iterable
    {
        $request = new ServerRequest([], [], null, null, 'php://memory');

        yield 'simple match' => (new RouteTestDefinition(
            new Method('GET'),
            $request->withMethod('GET')
        ))
            ->expectMatchResult(
                RouteResult::fromRouteMatch([])
            )
            ->expectPartialMatchResult(
                RouteResult::fromRouteMatch([])
            );

        yield 'match comma separated verbs' => (new RouteTestDefinition(
            new Method('get,post'),
            $request->withMethod('POST')
        ))
            ->expectMatchResult(
                RouteResult::fromRouteMatch([])
            )
            ->expectPartialMatchResult(
                RouteResult::fromRouteMatch([])
            );

        yield 'match comma separated verbs with whitespace' => (new RouteTestDefinition(
            new Method('get ,    post , put'),
            $request->withMethod('POST')
        ))
            ->expectMatchResult(
                RouteResult::fromRouteMatch([])
            )
            ->expectPartialMatchResult(
                RouteResult::fromRouteMatch([])
            );

        yield 'match ignores case' => (new RouteTestDefinition(
            new Method('Get'),
            $request->withMethod('get')
        ))
            ->expectMatchResult(
                RouteResult::fromRouteMatch([])
            )
            ->expectPartialMatchResult(
                RouteResult::fromRouteMatch([])
            );

        yield 'no match gives list of allowed methods' => (new RouteTestDefinition(
            new Method('POST,PUT,DELETE'),
            $request->withMethod('GET')
        ))
            ->expectMatchResult(
                RouteResult::fromMethodFailure(['POST', 'PUT', 'DELETE'])
            )
            ->expectPartialMatchResult(
                RouteResult::fromMethodFailure(['POST', 'PUT', 'DELETE'])
            );

        yield 'force fail option forces method failure' => (new RouteTestDefinition(
            new Method('GET,POST'),
            $request->withMethod('GET')
        ))
            ->useMatchOptions([Method::OPTION_FORCE_FAILURE => true])
            ->expectMatchResult(
                RouteResult::fromMethodFailure(['GET', 'POST'])
            )
            ->expectPartialMatchResult(
                RouteResult::fromMethodFailure(['GET', 'POST'])
            );
    }

    public function testAssemblePassesUriAndParametersToNextAndReturnsResult()
    {
        $uri = new Uri();
        $expectedUri = new Uri();
        $method = new Method('get');
        /** @var RouteInterface|MockObject $next */
        $next = $this->getMockBuilder(RouteInterface::class)
            ->getMock();
        $next->expects($this->once())
            ->method('assemble')
            ->with($uri, ['foo' => 'bar'], ['baz' => 'qux'])
            ->willReturn($expectedUri);

        $this->assertSame($expectedUri, $method->assemblePartial($uri, $next, ['foo' => 'bar'], ['baz' => 'qux']));
    }

    public function testRejectsNegativePathOffset()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Path offset cannot be negative');
        $request = $this->prophesize(ServerRequestInterface::class);
        $route = new Method('GET');
        $route->matchPartial($request->reveal(), FullMatch::getInstance(), -1);
    }
}
