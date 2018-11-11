<?php
/**
 *  @see       https://github.com/zendframework/zend-router for the canonical source repository
 *  @copyright Copyright (c) 2015-2018 Zend Technologies USA Inc. (https://www.zend.com)
 *  @license   https://github.com/zendframework/zend-router/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Router\Route\TestAsset;

use PHPUnit\Framework\Assert;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\UriInterface;
use Zend\Router\Route\PartialRouteInterface;
use Zend\Router\RouteInterface;
use Zend\Router\RouteResult;

use function ksort;
use function sort;

trait PartialRouteTestTrait
{
    use RouteTestTrait;

    abstract public function getRouteTestDefinitions() : iterable;

    /**
     * @uses self::getRouteTestDefinitions() provided definitions to prepare and
     *     provide data for partial route matching test
     */
    public function partialRouteMatchingProvider() : array
    {
        $data = [];
        $definitions = $this->getRouteTestDefinitions();
        foreach ($definitions as $description => $definition) {
            /**
             * @var RouteTestDefinition $definition
             */
            $data[$description] = [
                $definition->getRoute(),
                $definition->getRequestToMatch(),
                $definition->getPathOffset(),
                $definition->getMatchOptions(),
                $definition->getExpectedPartialMatchResult(),
            ];
        }
        return $data;
    }

    /**
     * We use callback instead of route instance so that we can get coverage
     * for all route configuration combinations.
     *
     * @dataProvider partialRouteMatchingProvider
     */
    public function testPartialMatching(
        PartialRouteInterface $route,
        Request $request,
        int $pathOffset,
        array $matchOptions,
        RouteResult $expectedResult
    ) {
        $next = new class () implements RouteInterface {
            public function match(Request $request, int $pathOffset = 0, array $options = []) : RouteResult
            {
                return RouteResult::fromRouteMatch([]);
            }
            public function assemble(UriInterface $uri, array $substitutions = [], array $options = []) : UriInterface
            {
                return $uri;
            }
        };
        $result = $route->matchPartial($request, $next, $pathOffset, $matchOptions);

        if ($expectedResult->isSuccess()) {
            Assert::assertTrue($result->isSuccess(), 'Expected successful routing');
            $expectedParams = $expectedResult->getMatchedParams();
            ksort($expectedParams);
            $actualParams = $result->getMatchedParams();
            ksort($expectedParams);
            Assert::assertEquals($expectedParams, $actualParams, 'Matched parameters do not meet test expectation');

            Assert::assertSame(
                $expectedResult->getMatchedRouteName(),
                $result->getMatchedRouteName(),
                'Expected matched route name do not meet test expectation'
            );
        }
        if ($expectedResult->isFailure()) {
            Assert::assertTrue($result->isFailure(), 'Failed routing is expected');
        }
        if ($expectedResult->isMethodFailure()) {
            Assert::assertTrue($result->isMethodFailure(), 'Http method routing failure is expected');

            $expectedMethods = $expectedResult->getAllowedMethods();
            sort($expectedMethods);
            $actualMethods = $result->getAllowedMethods();
            sort($actualMethods);

            Assert::assertEquals($expectedMethods, $actualMethods, 'Allowed http methods do not match expectation');
        }
    }
}
