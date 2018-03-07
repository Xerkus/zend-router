<?php
/**
 * @link      http://github.com/zendframework/zend-router for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Router\Route;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Uri;
use Zend\Router\Exception\InvalidArgumentException;
use Zend\Router\Route\Partial\FullMatch;
use Zend\Router\Route\Regex;
use Zend\Router\RouteResult;
use ZendTest\Router\Route\TestAsset\PartialRouteTestTrait;
use ZendTest\Router\Route\TestAsset\RouteTestDefinition;

/**
 * @covers \Zend\Router\Route\Regex
 */
class RegexTest extends TestCase
{
    use PartialRouteTestTrait;

    public function getRouteTestDefinitions() : iterable
    {
        $params = ['foo' => 'bar'];
        yield 'simple match' => (new RouteTestDefinition(
            new Regex('/(?<foo>[^/]+)', '/%foo%'),
            new Uri('/bar')
        ))
            ->expectMatchResult(
                RouteResult::fromRouteMatch($params)
            )
            ->expectPartialMatchResult(
                RouteResult::fromRouteMatch($params)
            )
            ->shouldAssembleAndExpectResultSameAsUriForMatching()
            ->useParamsForAssemble($params);

        yield 'no match without leading slash' => (new RouteTestDefinition(
            new Regex('(?<foo>[^/]+)', '%foo%'),
            new Uri('/bar')
        ))
            ->expectMatchResult(
                RouteResult::fromRouteFailure()
            )
            ->expectPartialMatchResult(
                RouteResult::fromRouteFailure()
            );

        yield 'only partial match with trailing slash' => (new RouteTestDefinition(
            new Regex('/(?<foo>[^/]+)', '/%foo%'),
            new Uri('/bar/')
        ))
            ->expectMatchResult(
                RouteResult::fromRouteFailure()
            )
            ->expectPartialMatchResult(
                RouteResult::fromRouteMatch(['foo' => 'bar'])
            );

        $params = ['foo' => 'bar'];
        yield 'offset skips beginning' => (new RouteTestDefinition(
            new Regex('(?<foo>[^/]+)', '%foo%'),
            new Uri('/bar')
        ))
            ->usePathOffset(1)
            ->expectMatchResult(
                RouteResult::fromRouteMatch($params)
            )
            ->expectPartialMatchResult(
                RouteResult::fromRouteMatch($params)
            )
            ->shouldAssembleAndExpectResult(new Uri('bar'))
            ->useParamsForAssemble($params);

        $params = ['foo' => 'foo bar'];
        yield 'url encoded parameters are decoded' => (new RouteTestDefinition(
            new Regex('/(?<foo>[^/]+)', '/%foo%'),
            new Uri('/foo%20bar')
        ))
            ->expectMatchResult(
                RouteResult::fromRouteMatch($params)
            )
            ->expectPartialMatchResult(
                RouteResult::fromRouteMatch($params)
            )
            ->shouldAssembleAndExpectResultSameAsUriForMatching()
            ->useParamsForAssemble($params);

        $params = ['bar' => 'bar', 'baz' => 'baz'];
        yield 'empty matches are replaced with defaults' => (new RouteTestDefinition(
            new Regex('/foo(?:/(?<bar>[^/]+))?/baz-(?<baz>[^/]+)', '/foo/baz-%baz%', ['bar' => 'bar']),
            new Uri('/foo/baz-baz')
        ))
            ->expectMatchResult(
                RouteResult::fromRouteMatch($params)
            )
            ->expectPartialMatchResult(
                RouteResult::fromRouteMatch($params)
            )
            ->shouldAssembleAndExpectResultSameAsUriForMatching()
            ->useParamsForAssemble($params);
    }

    public function testRawDecode()
    {
        // verify all characters which don't absolutely require encoding pass through match unchanged
        // this includes every character other than #, %, / and ?
        $raw = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789`-=[]\\;\',.~!@$^&*()_+{}|:"<>';
        $request = new ServerRequest([], [], new Uri('http://example.com/' . $raw));
        $route = new Regex('/(?<foo>[^/]+)', '/%foo%');
        $result = $route->match($request);

        $this->assertTrue($result->isSuccess());
        $this->assertSame($raw, $result->getMatchedParams()['foo']);
    }

    public function testEncodedDecode()
    {
        // @codingStandardsIgnoreStart
        // every character
        $in  = '%61%62%63%64%65%66%67%68%69%6a%6b%6c%6d%6e%6f%70%71%72%73%74%75%76%77%78%79%7a%41%42%43%44%45%46%47%48%49%4a%4b%4c%4d%4e%4f%50%51%52%53%54%55%56%57%58%59%5a%30%31%32%33%34%35%36%37%38%39%60%2d%3d%5b%5d%5c%3b%27%2c%2e%2f%7e%21%40%23%24%25%5e%26%2a%28%29%5f%2b%7b%7d%7c%3a%22%3c%3e%3f';
        $out = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789`-=[]\\;\',./~!@#$%^&*()_+{}|:"<>?';
        // @codingStandardsIgnoreEnd

        $request = new ServerRequest([], [], new Uri('http://example.com/' . $in));
        $route = new Regex('/(?<foo>[^/]+)', '/%foo%');
        $result = $route->match($request);

        $this->assertSame($out, $result->getMatchedParams()['foo']);
    }

    public function testRejectsNegativePathOffset()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Path offset cannot be negative');
        $request = $this->prophesize(ServerRequestInterface::class);
        $route = new Regex('/foo', '/%foo%');
        $route->matchPartial($request->reveal(), FullMatch::getInstance(), -1);
    }
}
