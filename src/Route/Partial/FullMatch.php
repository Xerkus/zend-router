<?php
/**
 *  @see       https://github.com/zendframework/zend-router for the canonical source repository
 *  @copyright Copyright (c) 2015-2018 Zend Technologies USA Inc. (https://www.zend.com)
 *  @license   https://github.com/zendframework/zend-router/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Router\Route\Partial;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\UriInterface;
use Zend\Router\Exception\RuntimeException;
use Zend\Router\RouteInterface;
use Zend\Router\RouteResult;

use function strlen;

/**
 * Special route used to ensure partial route requires a full URI path match
 * for successful routing when used as a standalone route
 *
 * @internal
 */
final class FullMatch implements RouteInterface
{
    /** @var self */
    private static $instance;

    /**
     * This special route is an implementation detail and should not be used
     * directly. It is not managed by route manager and is provided as singleton
     */
    public static function getInstance() : self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Match a given request.
     *
     * @param int $pathOffset URI path offset to use for matching
     */
    public function match(Request $request, int $pathOffset = 0, array $options = []) : RouteResult
    {
        $pathLength = strlen($request->getUri()->getPath());
        if ($pathLength === $pathOffset) {
            return RouteResult::fromRouteMatch([]);
        }

        return RouteResult::fromRouteFailure();
    }

    /**
     * Generate a URI
     *
     * @param UriInterface $uri Base URI instance. Assembled URI path should
     *      append to path present in base URI.
     * @throws RuntimeException if unable to generate the given URI
     */
    public function assemble(UriInterface $uri, array $substitutions = [], array $options = []) : UriInterface
    {
        return $uri;
    }
}
