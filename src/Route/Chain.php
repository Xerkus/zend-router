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
use SplQueue;
use Zend\Router\Exception\InvalidArgumentException;
use Zend\Router\Route\Partial\FullMatch;
use Zend\Router\RouteInterface;
use Zend\Router\RouteResult;

use function get_class;
use function gettype;
use function is_object;
use function sprintf;

/**
 * Chain route.
 */
class Chain implements RouteInterface
{
    /** @var SplQueue|PartialRouteInterface[] */
    private $chain;

    /** @var RouteInterface */
    private $chainEnd;

    /**
     * Create a new chain route.
     *
     * @param PartialRouteInterface[] $routes
     */
    public function __construct(iterable $routes, ?RouteInterface $chainEnd = null)
    {
        $this->chain = new SplQueue();
        foreach ($routes as $route) {
            if (! $route instanceof PartialRouteInterface) {
                throw new InvalidArgumentException(sprintf(
                    'Chained route must be instance of %s but %s given',
                    PartialRouteInterface::class,
                    is_object($route) ? get_class($route) : gettype($route)
                ));
            }
            $this->chain->enqueue($route);
        }

        $this->chainEnd = $chainEnd ?? FullMatch::getInstance();
    }

    /**
     * Match a given request.
     */
    public function match(Request $request, int $pathOffset = 0, array $options = []) : RouteResult
    {
        if ($this->chain->isEmpty()) {
            return $this->chainEnd->match($request, $pathOffset, $options);
        }

        $next = clone $this;
        /** @var PartialRouteInterface $route */
        $route = $next->chain->dequeue();

        return $route->matchPartial($request, $next, $pathOffset, $options);
    }

    /**
     * Assemble uri for the route.
     */
    public function assemble(UriInterface $uri, array $substitutions = [], array $options = []) : UriInterface
    {
        if ($this->chain->isEmpty()) {
            return $this->chainEnd->assemble($uri, $substitutions, $options);
        }

        $next = clone $this;
        /** @var PartialRouteInterface $route */
        $route = $next->chain->dequeue();

        return $route->assemblePartial($uri, $next, $substitutions, $options);
    }
}
