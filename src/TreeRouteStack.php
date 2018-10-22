<?php
/**
 * @link      http://github.com/zendframework/zend-router for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

declare(strict_types=1);

namespace Zend\Router;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\UriInterface;
use Zend\Router\Exception\InvalidArgumentException;
use Zend\Router\Exception\RuntimeException;

use function array_merge;
use function explode;
use function sprintf;

/**
 * Tree search implementation.
 */
class TreeRouteStack extends SimpleRouteStack
{
    /**
     * Match a given request. Prepend route name to matched route name present in RouteResult
     *
     * @param int $pathOffset URI path offset to use for matching
     */
    public function match(Request $request, int $pathOffset = 0, array $options = []) : RouteResult
    {
        $methodFailureResults = [];
        foreach ($this->routes as $name => $route) {
            /** @var RouteInterface $route */
            $result = $route->match($request, $pathOffset, $options);
            if ($result->isSuccess()) {
                $result = $result->withMatchedRouteName($name, RouteResult::NAME_PREPEND);
                if (empty($this->defaultParams)) {
                    return $result;
                }
                return $result->withMatchedParams($result->getMatchedParams() + $this->defaultParams);
            }
            if ($result->isMethodFailure()) {
                $methodFailureResults[] = $result;
            }
        }
        if (! empty($methodFailureResults)) {
            // micro optimisation
            if (! isset($methodFailureResults[1])) {
                return $methodFailureResults[0];
            }
            $methods = [];
            foreach ($methodFailureResults as $failureResult) {
                /** @var RouteResult $failureResult */
                $methods = array_merge($methods, $failureResult->getAllowedMethods());
            }
            RouteResult::fromMethodFailure($methods);
        }
        return RouteResult::fromRouteFailure();
    }

    /**
     * Generate a URI. Split name using "/" as separator, use first segment as
     * route name and pass remainder to the route as option
     *
     * @param UriInterface $uri Base URI instance. Assembled URI path should
     *      append to path present in base URI.
     * @throws RuntimeException if unable to generate the given URI
     * @throws InvalidArgumentException If required option "name" is not present
     */
    public function assemble(UriInterface $uri, array $substitutions = [], array $options = []) : UriInterface
    {
        if (! isset($options['name'])) {
            throw new InvalidArgumentException('Missing "name" option');
        }

        $names = explode('/', $options['name'], 2);
        $route = $this->routes->get($names[0]);

        if (! $route) {
            throw new RuntimeException(sprintf('Route with name "%s" not found', $names[0]));
        }

        unset($options['name']);
        if (isset($names[1])) {
            if (! $route instanceof RouteStackInterface) {
                throw new RuntimeException(sprintf(
                    'Route with name "%s" does not have child routes',
                    $names[0]
                ));
            }
            $options['name'] = $names[1];
        }

        return $route->assemble($uri, array_merge($this->defaultParams, $substitutions), $options);
    }
}
