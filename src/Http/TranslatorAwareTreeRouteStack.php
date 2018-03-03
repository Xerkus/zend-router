<?php
/**
 * @link      http://github.com/zendframework/zend-router for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

declare(strict_types=1);

namespace Zend\Router\Http;

use Zend\I18n\Translator\TranslatorAwareInterface;
use Zend\I18n\Translator\TranslatorInterface as Translator;
use Zend\Router\Exception;
use Zend\Router\TreeRouteStack;
use Zend\Stdlib\RequestInterface as Request;

/**
 * Translator aware tree route stack.
 */
class TranslatorAwareTreeRouteStack extends TreeRouteStack implements TranslatorAwareInterface
{
    /**
     * Translator used for translatable segments.
     *
     * @var Translator
     */
    protected $translator;

    /**
     * Whether the translator is enabled.
     *
     * @var bool
     */
    protected $translatorEnabled = true;

    /**
     * Translator text domain to use.
     *
     * @var string
     */
    protected $translatorTextDomain = 'default';

    /**
     * match(): defined by \Zend\Router\RouteInterface
     *
     * @see    \Zend\Router\RouteInterface::match()
     * @param  Request      $request
     * @param  int|null $pathOffset
     * @param  array        $options
     * @return RouteMatch|null
     */
    public function match(Request $request, $pathOffset = null, array $options = [])
    {
        if ($this->hasTranslator() && $this->isTranslatorEnabled() && ! isset($options['translator'])) {
            $options['translator'] = $this->getTranslator();
        }

        if (! isset($options['text_domain'])) {
            $options['text_domain'] = $this->getTranslatorTextDomain();
        }

        return parent::match($request, $pathOffset, $options);
    }

    /**
     * assemble(): defined by \Zend\Router\RouteInterface interface.
     *
     * @see    \Zend\Router\RouteInterface::assemble()
     * @param  array $params
     * @param  array $options
     * @return mixed
     * @throws Exception\InvalidArgumentException
     * @throws Exception\RuntimeException
     */
    public function assemble(array $params = [], array $options = [])
    {
        if ($this->hasTranslator() && $this->isTranslatorEnabled() && ! isset($options['translator'])) {
            $options['translator'] = $this->getTranslator();
        }

        if (! isset($options['text_domain'])) {
            $options['text_domain'] = $this->getTranslatorTextDomain();
        }

        return parent::assemble($params, $options);
    }

    /**
     * setTranslator(): defined by TranslatorAwareInterface.
     *
     * @see    TranslatorAwareInterface::setTranslator()
     * @param  Translator $translator
     * @param  string     $textDomain
     * @return TreeRouteStack
     */
    public function setTranslator(?Translator $translator = null, $textDomain = null)
    {
        $this->translator = $translator;

        if ($textDomain !== null) {
            $this->setTranslatorTextDomain($textDomain);
        }

        return $this;
    }

    /**
     * getTranslator(): defined by TranslatorAwareInterface.
     *
     * @see    TranslatorAwareInterface::getTranslator()
     * @return Translator
     */
    public function getTranslator()
    {
        return $this->translator;
    }

    /**
     * hasTranslator(): defined by TranslatorAwareInterface.
     *
     * @see    TranslatorAwareInterface::hasTranslator()
     * @return bool
     */
    public function hasTranslator()
    {
        return $this->translator !== null;
    }

    /**
     * setTranslatorEnabled(): defined by TranslatorAwareInterface.
     *
     * @see    TranslatorAwareInterface::setTranslatorEnabled()
     * @param  bool $enabled
     * @return TreeRouteStack
     */
    public function setTranslatorEnabled($enabled = true)
    {
        $this->translatorEnabled = $enabled;
        return $this;
    }

    /**
     * isTranslatorEnabled(): defined by TranslatorAwareInterface.
     *
     * @see    TranslatorAwareInterface::isTranslatorEnabled()
     * @return bool
     */
    public function isTranslatorEnabled()
    {
        return $this->translatorEnabled;
    }

    /**
     * setTranslatorTextDomain(): defined by TranslatorAwareInterface.
     *
     * @see    TranslatorAwareInterface::setTranslatorTextDomain()
     * @param  string $textDomain
     * @return self
     */
    public function setTranslatorTextDomain($textDomain = 'default')
    {
        $this->translatorTextDomain = $textDomain;

        return $this;
    }

    /**
     * getTranslatorTextDomain(): defined by TranslatorAwareInterface.
     *
     * @see    TranslatorAwareInterface::getTranslatorTextDomain()
     * @return string
     */
    public function getTranslatorTextDomain()
    {
        return $this->translatorTextDomain;
    }
}
