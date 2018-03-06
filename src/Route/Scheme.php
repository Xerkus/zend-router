<?php
/**
 * @link      http://github.com/zendframework/zend-router for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

declare(strict_types=1);

namespace Zend\Router\Route;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\UriInterface;
use Zend\Router\Exception\InvalidArgumentException;
use Zend\Router\RouteInterface;
use Zend\Router\RouteResult;

use function array_merge;

/**
 * Scheme route.
 */
class Scheme implements PartialRouteInterface
{
    use PartialRouteTrait;

    /**
     * Scheme to match.
     *
     * @var string
     */
    protected $scheme;

    /**
     * Default values.
     *
     * @var array
     */
    protected $defaults;

    /**
     * Create a new scheme route.
     */
    public function __construct(string $scheme, array $defaults = [])
    {
        $this->scheme = $scheme;
        $this->defaults = $defaults;
    }

    public function matchPartial(
        Request $request,
        RouteInterface $next,
        int $pathOffset = 0,
        array $options = []
    ) : RouteResult {
        if ($pathOffset < 0) {
            throw new InvalidArgumentException('Path offset cannot be negative');
        }
        $uri = $request->getUri();
        $scheme = $uri->getScheme();

        if ($scheme !== $this->scheme) {
            return RouteResult::fromRouteFailure();
        }

        $result = $next->match($request, $pathOffset, $options);

        if ($result->isFailure()) {
            return $result;
        }

        if (empty($this->defaults)) {
            return $result;
        }

        return $result->withMatchedParams(array_merge($this->defaults, $result->getMatchedParams()));
    }

    public function assemblePartial(
        UriInterface $uri,
        RouteInterface $next,
        array $substitutions = [],
        array $options = []
    ) : UriInterface {
        $uri = $uri->withScheme($this->scheme);
        return $next->assemble($uri, $substitutions, $options);
    }
}
