<?php
/**
 * @link      http://github.com/zendframework/zend-router for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
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
use function is_int;
use function is_numeric;
use function preg_match;
use function rawurldecode;
use function rawurlencode;
use function str_replace;
use function strlen;
use function strpos;

/**
 * Regex route.
 */
class Regex implements PartialRouteInterface
{
    use PartialRouteTrait;

    /**
     * Regex to match.
     *
     * @var string
     */
    protected $regex;

    /**
     * Default values.
     *
     * @var array
     */
    protected $defaults;

    /**
     * Specification for URL assembly.
     *
     * Parameters accepting substitutions should be denoted as "%key%"
     *
     * @var string
     */
    protected $spec;

    /**
     * Create a new regex route.
     */
    public function __construct(string $regex, string $spec, array $defaults = [])
    {
        $this->regex = $regex;
        $this->spec = $spec;
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
        $uri = $request->getUri();
        $path = $uri->getPath();

        $result = preg_match('(\G' . $this->regex . ')', $path, $matches, 0, $pathOffset);

        if (! $result) {
            return RouteResult::fromRouteFailure();
        }

        $matchedLength = strlen($matches[0]);

        foreach ($matches as $key => $value) {
            if (is_numeric($key) || is_int($key) || $value === '') {
                unset($matches[$key]);
            } else {
                $matches[$key] = rawurldecode($value);
            }
        }

        $result = $next->match($request, $pathOffset + $matchedLength, $options);

        if ($result->isFailure()) {
            return $result;
        }

        if (empty($this->defaults) && empty($matches)) {
            return $result;
        }

        return $result->withMatchedParams(array_merge($this->defaults, $matches, $result->getMatchedParams()));
    }

    public function assemblePartial(
        UriInterface $uri,
        RouteInterface $next,
        array $substitutions = [],
        array $options = []
    ) : UriInterface {
        $url = $this->spec;
        $mergedParams = array_merge($this->defaults, $substitutions);
        $assembledParams = [];

        foreach ($mergedParams as $key => $value) {
            $spec = '%' . $key . '%';

            if (strpos($url, $spec) !== false) {
                $url = str_replace($spec, rawurlencode($value), $url);

                $assembledParams[] = $key;
            }
        }

        $uri = $uri->withPath($uri->getPath() . $url);
        // @TODO pass assembled params as options to next routes
        return $next->assemble($uri, $substitutions, $options);
    }
}
