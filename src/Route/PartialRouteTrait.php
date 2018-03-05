<?php
/**
 * @link      http://github.com/zendframework/zend-router for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

declare(strict_types=1);

namespace Zend\Router\Route;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\UriInterface;
use Zend\Router\Exception\InvalidArgumentException;
use Zend\Router\Route\Partial\FullMatch;
use Zend\Router\RouteResult;

/**
 * Provides default implementation for match() and assemble for partial routes
 *
 * @see PartialRouteInterface
 */
trait PartialRouteTrait
{
    /**
     * Attempts to match a request by delegating to {@see PartialRouteInterface::matchPartial()}.
     *
     * Returns route result from partial match
     *
     * @throws InvalidArgumentException
     */
    public function match(Request $request, int $pathOffset = 0, array $options = []) : RouteResult
    {
        if ($pathOffset < 0) {
            throw new InvalidArgumentException('Path offset cannot be negative');
        }

        $next = FullMatch::getInstance();
        /** @var PartialRouteInterface $this */
        return $this->matchPartial($request, $next, $pathOffset, $options);
    }

    /**
     * Attempts to assemble URI by delegating to {@see PartialRouteInterface::assemblePartial()}.
     */
    public function assemble(UriInterface $uri, array $substitutions = [], array $options = []) : UriInterface
    {
        $next = FullMatch::getInstance();
        /** @var PartialRouteInterface $this */
        return $this->assemblePartial($uri, $next, $substitutions, $options);
    }
}
