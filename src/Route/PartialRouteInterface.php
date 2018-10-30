<?php
/**
 *  @see       https://github.com/zendframework/zend-router for the canonical source repository
 *  @copyright Copyright (c) 2015-2018 Zend Technologies USA Inc. (https://www.zend.com)
 *  @license   https://github.com/zendframework/zend-router/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Router\Route;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\UriInterface;
use Zend\Router\RouteInterface;
use Zend\Router\RouteResult;

/**
 * Route capable of performing partial match.
 */
interface PartialRouteInterface extends RouteInterface
{
    /**
     * Match a given request
     *
     * @param int $pathOffset URI path offset to use for matching
     */
    public function matchPartial(
        Request $request,
        RouteInterface $next,
        int $pathOffset = 0,
        array $options = []
    ) : RouteResult;

    public function assemblePartial(
        UriInterface $uri,
        RouteInterface $next,
        array $substitutions = [],
        array $options = []
    ) : UriInterface;
}
