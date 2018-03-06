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
use Psr\Http\Message\UriInterface;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Uri;
use Zend\Router\Exception\InvalidArgumentException;
use Zend\Router\Route\Partial\FullMatch;
use Zend\Router\Route\Scheme;
use Zend\Router\RouteInterface;

/**
 * @covers \Zend\Router\Route\Scheme
 */
class SchemeTest extends TestCase
{
    /** @var ServerRequestInterface */
    private $request;

    protected function setUp()
    {
        $this->request = new ServerRequest([], [], null, null, 'php://memory');
    }

    public function testMatching()
    {
        $request = $this->request->withUri((new Uri())->withScheme('https'));

        $route = new Scheme('https');
        $result = $route->match($request);

        $this->assertTrue($result->isSuccess());
    }

    public function testMatchReturnsResultWithDefaultParameters()
    {
        $request = $this->request->withUri((new Uri())->withScheme('https'));

        $route = new Scheme('https', ['foo' => 'bar']);
        $result = $route->match($request);

        $this->assertEquals(['foo' => 'bar'], $result->getMatchedParams());
    }

    public function testNoMatchingOnDifferentScheme()
    {
        $request = $this->request->withUri((new Uri())->withScheme('http'));

        $route = new Scheme('https');
        $result = $route->match($request);

        $this->assertTrue($result->isFailure());
    }

    public function testAssembling()
    {
        $uri = new Uri();
        $route = new Scheme('https');
        $resultUri = $route->assemble($uri);

        $this->assertEquals('https', $resultUri->getScheme());
    }

    public function testAssemblePassesUriAndParametersToNextAndReturnsResult()
    {
        $route = new Scheme('https');
        /** @var RouteInterface|MockObject $next */
        $next = $this->getMockBuilder(RouteInterface::class)
            ->getMock();
        $next->expects($this->once())
            ->method('assemble')
            ->with($this->anything(), ['foo' => 'bar'], ['baz' => 'qux'])
            ->willReturnCallback(function (UriInterface $uri) {
                return $uri->withPath('/foo');
            });

        $uri = $route->assemblePartial(new Uri(), $next, ['foo' => 'bar'], ['baz' => 'qux']);
        $this->assertSame('https', $uri->getScheme());
        $this->assertSame('/foo', $uri->getPath());
    }


    public function testRejectsNegativePathOffset()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Path offset cannot be negative');
        $request = $this->prophesize(ServerRequestInterface::class);
        $route = new Scheme('https');
        $route->matchPartial($request->reveal(), FullMatch::getInstance(), -1);
    }
}
