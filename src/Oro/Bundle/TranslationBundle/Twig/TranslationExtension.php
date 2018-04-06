<?php

namespace Oro\Bundle\TranslationBundle\Twig;

use Oro\Bundle\TranslationBundle\Helper\TranslationsDatagridRouteHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\RouterInterface;

class TranslationExtension extends \Twig_Extension
{
    const NAME = 'oro_translation';

    /** @var ContainerInterface */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return TranslationsDatagridRouteHelper
     */
    protected function getTranslationsDatagridRouteHelper()
    {
        return $this->container->get('oro_translation.helper.translation_route');
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('oro_translation_debug_translator', [$this, 'isDebugTranslator']),
            new \Twig_SimpleFunction('oro_translation_debug_js_translations', [$this, 'isDebugJsTranslations']),
            new \Twig_SimpleFunction('translation_grid_link', [$this, 'getTranslationGridLink'])
        ];
    }

    /**
     * @return bool
     */
    public function isDebugTranslator()
    {
        return $this->container->getParameter('oro_translation.debug_translator');
    }

    /**
     * @return bool
     */
    public function isDebugJsTranslations()
    {
        return $this->container->getParameter('oro_translation.js_translation.debug');
    }

    /**
     * @param array $filters
     * @param int   $referenceType
     *
     * @return string
     */
    public function getTranslationGridLink(array $filters = [], $referenceType = RouterInterface::ABSOLUTE_PATH)
    {
        return $this->getTranslationsDatagridRouteHelper()->generate($filters, $referenceType);
    }
}
