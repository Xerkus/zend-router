<?php
/**
 * @see       https://github.com/zendframework/zend-router for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-router/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Router\Route;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Uri;
use Zend\Router\Exception\InvalidArgumentException;
use Zend\Router\Route\Partial\FullMatch;
use Zend\Router\Route\PartialRouteInterface;
use Zend\Router\Route\PartialRouteTrait;
use Zend\Router\RouteResult;

/**
 * @covers \Zend\Router\Route\PartialRouteTrait
 */
class PartialRouteTraitTest extends TestCase
{
    /** @var PartialRouteInterface|MockObject */
    private $partialTrait;

    /** @var ServerRequestInterface */
    private $request;

    protected function setUp()
    {
        $this->request = new ServerRequest([], [], '/path');
        $this->partialTrait = $this->getMockForTrait(
            PartialRouteTrait::class,
            [],
            '',
            true,
            true,
            true,
            ['matchPartial', 'assemblePartial']
        );
    }

    public function testMatchDelegatesToPartialMatchWithProvidedParameters()
    {
        $result = RouteResult::fromRouteMatch([]);
        $this->partialTrait
            ->expects($this->once())
            ->method('matchPartial')
            ->with($this->request, $this->anything(), 5, ['foo' => 'bar'])
            ->willReturn($result);

        $this->partialTrait->match($this->request, 5, ['foo' => 'bar']);
    }

    public function testMatchReturnsResultFromMatchPartial()
    {
        $result = RouteResult::fromRouteMatch([]);
        $this->partialTrait
            ->expects($this->once())
            ->method('matchPartial')
            ->willReturn($result);

        $returned = $this->partialTrait->match($this->request);

        $this->assertSame($result, $returned);
    }

    public function testRejectsNegativePathOffset()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Path offset cannot be negative');
        $this->partialTrait->match($this->request, -1);
    }

    public function testMatchPassesSpecialFullPathRouteAsNextRoute()
    {
        $result = RouteResult::fromRouteMatch([]);
        $next = FullMatch::getInstance();
        $this->partialTrait
            ->expects($this->once())
            ->method('matchPartial')
            ->with($this->anything(), $next)
            ->willReturn($result);

        $this->partialTrait->match($this->request);
    }

    public function testAssembleDelegatesToPartialAssembleWithProvidedParameters()
    {
        $uri = new Uri();
        $this->partialTrait
            ->expects($this->once())
            ->method('assemblePartial')
            ->with($uri, $this->anything(), ['foo' => 'bar'], ['baz' => 'qux'])
            ->willReturn($uri);

        $this->partialTrait->assemble($uri, ['foo' => 'bar'], ['baz' => 'qux']);
    }

    public function testAssembleReturnsUriFromAssemblePartial()
    {
        $uri = new Uri();
        $this->partialTrait
            ->expects($this->once())
            ->method('assemblePartial')
            ->willReturn($uri);

        $returned = $this->partialTrait->assemble(new Uri());

        $this->assertSame($uri, $returned);
    }

    public function testAssemblePassesSpecialFullPathRouteAsNextRoute()
    {
        $uri = new Uri();
        $next = FullMatch::getInstance();
        $this->partialTrait
            ->expects($this->once())
            ->method('assemblePartial')
            ->with($this->anything(), $next)
            ->willReturn($uri);

        $this->partialTrait->assemble($uri);
    }
}
