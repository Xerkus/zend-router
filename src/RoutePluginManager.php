<?php
/**
 * @link      http://github.com/zendframework/zend-router for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

declare(strict_types=1);

namespace Zend\Router;

use Interop\Container\ContainerInterface;
use Zend\Router\Route\Chain;
use Zend\Router\Route\Hostname;
use Zend\Router\Route\Literal;
use Zend\Router\Route\Method;
use Zend\Router\Route\Part;
use Zend\Router\Route\Placeholder;
use Zend\Router\Route\Regex;
use Zend\Router\Route\Scheme;
use Zend\Router\Route\Segment;
use Zend\ServiceManager\AbstractPluginManager;
use Zend\ServiceManager\ConfigInterface;

/**
 * Plugin manager implementation for routes
 *
 * Enforces that routes retrieved are instances of RouteInterface.
 *
 * The manager is marked to not share by default, in order to allow multiple
 * route instances of the same type.
 */
class RoutePluginManager extends AbstractPluginManager
{
    /**
     * Only RouteInterface instances are valid
     *
     * @var string
     */
    protected $instanceOf = RouteInterface::class;

    /**
     * Do not share instances. (v3)
     *
     * @var bool
     */
    protected $shareByDefault = false;


    /**
     * Constructor
     *
     * Ensure that the instance is seeded with the RouteInvokableFactory as an
     * abstract factory.
     *
     * @param ContainerInterface|ConfigInterface $configOrContainerInstance
     * @param array $v3config
     */
    public function __construct($configOrContainerInstance, array $v3config = [])
    {
        $this->configureDefaults();
        parent::__construct($configOrContainerInstance, $v3config);
    }

    protected function configureDefaults() : void
    {
        $this->configure([
            'aliases' => [
                'chain'    => Chain::class,
                'Chain'    => Chain::class,
                'hostname' => Hostname::class,
                'Hostname' => Hostname::class,
                'literal'  => Literal::class,
                'Literal'  => Literal::class,
                'method'   => Method::class,
                'Method'   => Method::class,
                'part'     => Part::class,
                'Part'     => Part::class,
                'placeholder' => Placeholder::class,
                'Placeholder' => Placeholder::class,
                'regex'    => Regex::class,
                'Regex'    => Regex::class,
                'scheme'   => Scheme::class,
                'Scheme'   => Scheme::class,
                'segment'  => Segment::class,
                'Segment'  => Segment::class,
                'Zend\Router\Http\Chain' => Chain::class,
                'Zend\Router\Http\Hostname' => Hostname::class,
                'Zend\Router\Http\Literal' => Literal::class,
                'Zend\Router\Http\Method' => Method::class,
                'Zend\Router\Http\Part' => Part::class,
                'Zend\Router\Http\Placeholder' => Placeholder::class,
                'Zend\Router\Http\Regex' => Regex::class,
                'Zend\Router\Http\Scheme' => Scheme::class,
                'Zend\Router\Http\Segment' => Segment::class,
            ],
            'abstract_factories' => [
                RouteInvokableFactory::class,
            ],
            'factories' => [
                Chain::class    => RouteInvokableFactory::class,
                Hostname::class => RouteInvokableFactory::class,
                Literal::class  => RouteInvokableFactory::class,
                Method::class   => RouteInvokableFactory::class,
                Part::class     => RouteInvokableFactory::class,
                Placeholder::class => RouteInvokableFactory::class,
                Regex::class    => RouteInvokableFactory::class,
                Scheme::class   => RouteInvokableFactory::class,
                Segment::class  => RouteInvokableFactory::class,
            ],
        ]);
    }
}
