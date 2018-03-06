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
use Zend\Router\RouteInterface;
use Zend\Router\RouteResult;

use function array_merge;
use function strlen;
use function strpos;

/**
 * Literal route.
 */
class Literal implements PartialRouteInterface
{
    use PartialRouteTrait;

    /**
     * Uri path to match
     *
     * @var string
     */
    private $path;

    /**
     * Default values.
     *
     * @var array
     */
    private $defaults;

    /**
     * Create a new literal route.
     *
     * @throws InvalidArgumentException on empty path
     */
    public function __construct(string $path, array $defaults = [])
    {
        if (empty($path)) {
            throw new InvalidArgumentException('Literal uri path part cannot be empty');
        }
        $this->path = $path;
        $this->defaults = $defaults;
    }

    /**
     * Attempt to match ServerRequestInterface by checking for literal
     * path segment at offset position.
     *
     * @throws InvalidArgumentException
     */
    public function matchPartial(
        Request $request,
        RouteInterface $next,
        int $pathOffset = 0,
        array $options = []
    ) : RouteResult {
        if ($pathOffset < 0) {
            throw new InvalidArgumentException('Path offset cannot be negative');
        }
        $path = $request->getUri()->getPath();

        if (strpos($path, $this->path, $pathOffset) !== $pathOffset) {
            return RouteResult::fromRouteFailure();
        }

        $pathOffset += strlen($this->path);
        $result = $next->match($request, $pathOffset, $options);

        if ($result->isFailure()) {
            return $result;
        }

        if (empty($this->defaults)) {
            return $result;
        }

        return $result->withMatchedParams(array_merge($this->defaults, $result->getMatchedParams()));
    }

    /**
     * Assemble url by appending literal path part
     */
    public function assemblePartial(
        UriInterface $uri,
        RouteInterface $next,
        array $substitutions = [],
        array $options = []
    ) : UriInterface {
        $uri = $uri->withPath($uri->getPath() . $this->path);
        return $next->assemble($uri, $substitutions, $options);
    }
}
