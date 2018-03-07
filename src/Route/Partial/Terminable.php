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
use Zend\Router\Exception\DomainException;
use Zend\Router\Route\PartialRouteInterface;
use Zend\Router\RouteInterface;
use Zend\Router\RouteResult;

use function sprintf;
use function strlen;

/**
 * @internal
 */
final class Terminable implements PartialRouteInterface
{
    private static $instance;

    public static function getInstance() : self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
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
    ) : RouteResult {
        if (strlen($request->getUri()->getPath()) === $pathOffset) {
            return RouteResult::fromRouteMatch([]);
        }
        return $next->match($request, $pathOffset, $options);
    }

    public function assemblePartial(
        UriInterface $uri,
        RouteInterface $next,
        array $substitutions = [],
        array $options = []
    ) : UriInterface {
        if (empty($options['name'])) {
            return $uri;
        }
        return $next->assemble($uri, $substitutions, $options);
    }

    /**
     * Match a given request.
     *
     * @param int $pathOffset URI path offset to use for matching
     */
    public function match(Request $request, int $pathOffset = 0, array $options = []) : RouteResult
    {
        throw new DomainException(sprintf(
            '%s is a special internal route and must not be used directly',
            self::class
        ));
    }

    /**
     * Generate a URI
     *
     * @param UriInterface $uri Base URI instance. Assembled URI path should
     *      append to path present in base URI.
     */
    public function assemble(UriInterface $uri, array $substitutions = [], array $options = []) : UriInterface
    {
        throw new DomainException(sprintf(
            '%s is a special internal route and must not be used directly',
            self::class
        ));
    }
}
