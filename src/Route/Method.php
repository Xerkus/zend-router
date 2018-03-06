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

use function array_intersect;
use function array_map;
use function array_merge;
use function explode;
use function in_array;
use function strtoupper;

/**
 * Method route.
 */
class Method implements PartialRouteInterface
{
    use PartialRouteTrait;

    public const OPTION_FORCE_FAILURE = self::class . '::force_failure';

    /**
     * Verb to match.
     *
     * @var string
     */
    protected $verb;

    /**
     * Default values.
     *
     * @var array
     */
    protected $defaults;

    /**
     * Create a new method route.
     */
    public function __construct(string $verb, array $defaults = [])
    {
        $this->verb = $verb;
        $this->defaults = $defaults;
    }

    /**
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

        $requestVerb = strtoupper($request->getMethod());
        $matchVerbs = explode(',', strtoupper($this->verb));
        $matchVerbs = array_map('trim', $matchVerbs);

        $methodFailure = false;
        $forceFail = $options[self::OPTION_FORCE_FAILURE] ?? false;
        if ($forceFail || ! in_array($requestVerb, $matchVerbs)) {
            $methodFailure = true;
            $options[self::OPTION_FORCE_FAILURE] = true;
        }

        $result = $next->match($request, $pathOffset, $options);

        if ($result->isMethodFailure()) {
            $methods = array_intersect($matchVerbs, $result->getAllowedMethods());
            if (empty($methods)) {
                return RouteResult::fromRouteFailure();
            }
            return RouteResult::fromMethodFailure($methods);
        }

        if ($result->isFailure()) {
            return $result;
        }

        if ($methodFailure) {
            return RouteResult::fromMethodFailure($matchVerbs);
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
        return $next->assemble($uri, $substitutions, $options);
    }
}
