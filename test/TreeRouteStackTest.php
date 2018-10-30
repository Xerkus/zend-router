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
use ReflectionClass;
use Zend\Http\PhpEnvironment\Request as PhpRequest;
use Zend\Http\Request;
use Zend\Router\Exception\InvalidArgumentException;
use Zend\Router\Exception\RuntimeException;
use Zend\Router\Http\Hostname;
use Zend\Router\TreeRouteStack;
use Zend\Stdlib\Request as BaseRequest;
use Zend\Uri\Http as HttpUri;
use ZendTest\Router\Http\TestAsset;
use ZendTest\Router\TestAsset\DummyRoute;

class TreeRouteStackTest extends TestCase
{
    public function testAddRouteRequiresHttpSpecificRoute()
    {
        $stack = new TreeRouteStack();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Route definition must be an array or Traversable object');
        $stack->addRoute('foo', new DummyRoute());
    }

    public function testAddRouteViaStringRequiresHttpSpecificRoute()
    {
        $stack = new TreeRouteStack();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Given route does not implement HTTP route interface');
        $stack->addRoute('foo', [
            'type' => DummyRoute::class,
        ]);
    }

    public function testAddRouteAcceptsTraversable()
    {
        $stack = new TreeRouteStack();
        $stack->addRoute('foo', new ArrayIterator([
            'type' => TestAsset\DummyRoute::class,
        ]));
    }

    public function testNoMatchWithoutUriMethod()
    {
        $stack = new TreeRouteStack();
        $request = new BaseRequest();

        $this->assertNull($stack->match($request));
    }

    public function testSetBaseUrlFromFirstMatch()
    {
        $stack = new TreeRouteStack();

        $request = new PhpRequest();
        $request->setBaseUrl('/foo');
        $stack->match($request);
        $this->assertEquals('/foo', $stack->getBaseUrl());

        $request = new PhpRequest();
        $request->setBaseUrl('/bar');
        $stack->match($request);
        $this->assertEquals('/foo', $stack->getBaseUrl());
    }

    public function testBaseUrlLengthIsPassedAsOffset()
    {
        $stack = new TreeRouteStack();
        $stack->setBaseUrl('/foo');
        $stack->addRoute('foo', [
            'type' => TestAsset\DummyRoute::class,
        ]);

        $this->assertEquals(4, $stack->match(new Request())->getParam('offset'));
    }

    public function testNoOffsetIsPassedWithoutBaseUrl()
    {
        $stack = new TreeRouteStack();
        $stack->addRoute('foo', [
            'type' => TestAsset\DummyRoute::class,
        ]);

        $this->assertEquals(null, $stack->match(new Request())->getParam('offset'));
    }

    public function testAssemble()
    {
        $stack = new TreeRouteStack();
        $stack->addRoute('foo', new TestAsset\DummyRoute());
        $this->assertEquals('', $stack->assemble([], ['name' => 'foo']));
    }

    public function testAssembleCanonicalUriWithoutRequestUri()
    {
        $stack = new TreeRouteStack();
        $stack->addRoute('foo', new TestAsset\DummyRoute());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Request URI has not been set');
        $stack->assemble([], ['name' => 'foo', 'force_canonical' => true]);
    }

    public function testAssembleCanonicalUriWithRequestUri()
    {
        $uri = new HttpUri('http://example.com:8080/');
        $stack = new TreeRouteStack();
        $stack->setRequestUri($uri);

        $stack->addRoute('foo', new TestAsset\DummyRoute());
        $this->assertEquals(
            'http://example.com:8080/',
            $stack->assemble([], ['name' => 'foo', 'force_canonical' => true])
        );
    }

    public function testAssembleCanonicalUriWithGivenUri()
    {
        $uri = new HttpUri('http://example.com:8080/');
        $stack = new TreeRouteStack();

        $stack->addRoute('foo', new TestAsset\DummyRoute());
        $this->assertEquals(
            'http://example.com:8080/',
            $stack->assemble([], ['name' => 'foo', 'uri' => $uri, 'force_canonical' => true])
        );
    }

    public function testAssembleCanonicalUriWithHostnameRoute()
    {
        $stack = new TreeRouteStack();
        $stack->addRoute('foo', new Hostname('example.com'));
        $uri = new HttpUri();
        $uri->setScheme('http');

        $this->assertEquals('http://example.com/', $stack->assemble([], ['name' => 'foo', 'uri' => $uri]));
    }

    public function testAssembleCanonicalUriWithHostnameRouteWithoutScheme()
    {
        $stack = new TreeRouteStack();
        $stack->addRoute('foo', new Hostname('example.com'));
        $uri = new HttpUri();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Request URI has not been set');
        $stack->assemble([], ['name' => 'foo', 'uri' => $uri]);
    }

    public function testAssembleCanonicalUriWithHostnameRouteAndRequestUriWithoutScheme()
    {
        $uri = new HttpUri();
        $uri->setScheme('http');
        $stack = new TreeRouteStack();
        $stack->setRequestUri($uri);
        $stack->addRoute('foo', new Hostname('example.com'));

        $this->assertEquals('http://example.com/', $stack->assemble([], ['name' => 'foo']));
    }

    public function testAssembleWithQueryParams()
    {
        $stack = new TreeRouteStack();
        $stack->addRoute(
            'index',
            [
                'type' => 'Literal',
                'options' => ['route' => '/'],
            ]
        );

        $this->assertEquals('/?foo=bar', $stack->assemble([], ['name' => 'index', 'query' => ['foo' => 'bar']]));
    }

    public function testAssembleWithEncodedPath()
    {
        $stack = new TreeRouteStack();
        $stack->addRoute(
            'index',
            [
                'type' => 'Literal',
                'options' => ['route' => '/this%2Fthat'],
            ]
        );

        $this->assertEquals('/this%2Fthat', $stack->assemble([], ['name' => 'index']));
    }

    public function testAssembleWithEncodedPathAndQueryParams()
    {
        $stack = new TreeRouteStack();
        $stack->addRoute(
            'index',
            [
                'type' => 'Literal',
                'options' => ['route' => '/this%2Fthat'],
            ]
        );

        $this->assertEquals(
            '/this%2Fthat?foo=bar',
            $stack->assemble([], ['name' => 'index', 'query' => ['foo' => 'bar'], 'normalize_path' => false])
        );
    }

    public function testAssembleWithScheme()
    {
        $uri = new HttpUri();
        $uri->setScheme('http');
        $uri->setHost('example.com');
        $stack = new TreeRouteStack();
        $stack->setRequestUri($uri);
        $stack->addRoute(
            'secure',
            [
                'type' => 'Scheme',
                'options' => ['scheme' => 'https'],
                'child_routes' => [
                    'index' => [
                        'type'    => 'Literal',
                        'options' => ['route'    => '/'],
                    ],
                ],
            ]
        );
        $this->assertEquals('https://example.com/', $stack->assemble([], ['name' => 'secure/index']));
    }

    public function testAssembleWithFragment()
    {
        $stack = new TreeRouteStack();
        $stack->addRoute(
            'index',
            [
                'type' => 'Literal',
                'options' => ['route' => '/'],
            ]
        );

        $this->assertEquals('/#foobar', $stack->assemble([], ['name' => 'index', 'fragment' => 'foobar']));
    }

    public function testAssembleWithoutNameOption()
    {
        $stack = new TreeRouteStack();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing "name" option');
        $stack->assemble();
    }

    public function testAssembleNonExistentRoute()
    {
        $stack = new TreeRouteStack();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Route with name "foo" not found');
        $stack->assemble([], ['name' => 'foo']);
    }

    public function testAssembleNonExistentChildRoute()
    {
        $stack = new TreeRouteStack();
        $stack->addRoute(
            'index',
            [
                'type' => 'Literal',
                'options' => ['route' => '/'],
            ]
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Route with name "index" does not have child routes');
        $stack->assemble([], ['name' => 'index/foo']);
    }

    public function testDefaultParamIsAddedToMatch()
    {
        $stack = new TreeRouteStack();
        $stack->setBaseUrl('/foo');
        $stack->addRoute('foo', new TestAsset\DummyRoute());
        $stack->setDefaultParam('foo', 'bar');

        $this->assertEquals('bar', $stack->match(new Request())->getParam('foo'));
    }

    public function testDefaultParamDoesNotOverrideParam()
    {
        $stack = new TreeRouteStack();
        $stack->setBaseUrl('/foo');
        $stack->addRoute('foo', new TestAsset\DummyRouteWithParam());
        $stack->setDefaultParam('foo', 'baz');

        $this->assertEquals('bar', $stack->match(new Request())->getParam('foo'));
    }

    public function testDefaultParamIsUsedForAssembling()
    {
        $stack = new TreeRouteStack();
        $stack->addRoute('foo', new TestAsset\DummyRouteWithParam());
        $stack->setDefaultParam('foo', 'bar');

        $this->assertEquals('bar', $stack->assemble([], ['name' => 'foo']));
    }

    public function testDefaultParamDoesNotOverrideParamForAssembling()
    {
        $stack = new TreeRouteStack();
        $stack->addRoute('foo', new TestAsset\DummyRouteWithParam());
        $stack->setDefaultParam('foo', 'baz');

        $this->assertEquals('bar', $stack->assemble(['foo' => 'bar'], ['name' => 'foo']));
    }

    public function testSetBaseUrl()
    {
        $stack = new TreeRouteStack();

        $this->assertEquals($stack, $stack->setBaseUrl('/foo/'));
        $this->assertEquals('/foo', $stack->getBaseUrl());
    }

    public function testSetRequestUri()
    {
        $uri = new HttpUri();
        $stack = new TreeRouteStack();

        $this->assertEquals($stack, $stack->setRequestUri($uri));
        $this->assertEquals($uri, $stack->getRequestUri());
    }
}
