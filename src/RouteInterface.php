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
use Zend\Router\Exception\RuntimeException;

/**
 * RouteInterface interface.
 */
interface RouteInterface
{
    /**
     * Match a given request.
     *
     * @param int $pathOffset URI path offset to use for matching
     */
    public function match(Request $request, int $pathOffset = 0, array $options = []) : RouteResult;

    /**
     * Generate a URI
     *
     * @param UriInterface $uri Base URI instance. Assembled URI path should
     *      append to path present in base URI.
     * @throws RuntimeException if unable to generate the given URI
     */
    public function assemble(UriInterface $uri, array $substitutions = [], array $options = []) : UriInterface;
}
