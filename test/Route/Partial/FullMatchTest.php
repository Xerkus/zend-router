<?php
/**
 *  @see       https://github.com/zendframework/zend-router for the canonical source repository
 *  @copyright Copyright (c) 2015-2018 Zend Technologies USA Inc. (https://www.zend.com)
 *  @license   https://github.com/zendframework/zend-router/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Router\Route\Partial;

use PHPUnit\Framework\TestCase;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Uri;
use Zend\Router\Route\Partial\FullMatch;

/**
 * @covers \Zend\Router\Route\Partial\FullMatch
 */
class FullMatchTest extends TestCase
{
    public function testSingleton()
    {
        $this->assertSame(
            FullMatch::getInstance(),
            FullMatch::getInstance()
        );
    }

    public function testMatchIsSuccessWhenNoPathLeftToMatch()
    {
        $request = new ServerRequest([], [], '/path');
        $route = FullMatch::getInstance();

        $result = $route->match($request, 5);
        $this->assertTrue($result->isSuccess(), 'Route result expected to be a success');
    }

    public function testMatchIsFailureIfNotFullPathMatch()
    {
        $request = new ServerRequest([], [], '/path');
        $route = FullMatch::getInstance();

        $result = $route->match($request, 4);
        $this->assertTrue($result->isFailure(), 'Route result expected to be a failure');
    }

    public function testAssembleReturnsUriVerbatim()
    {
        $route = FullMatch::getInstance();
        $uri = new Uri();
        $returnedUri = $route->assemble($uri);

        $this->assertSame($uri, $returnedUri);
    }
}
