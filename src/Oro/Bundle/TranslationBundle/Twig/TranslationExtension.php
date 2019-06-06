<?php

namespace Oro\Bundle\TranslationBundle\Twig;

use Oro\Bundle\TranslationBundle\Helper\TranslationsDatagridRouteHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\RouterInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides Twig functions to debug translations:
 *   - oro_translation_debug_translator
 *   - oro_translation_debug_js_translations
 *   - translation_grid_link
 */
class TranslationExtension extends AbstractExtension
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
            new TwigFunction('oro_translation_debug_translator', [$this, 'isDebugTranslator']),
            new TwigFunction('oro_translation_debug_js_translations', [$this, 'isDebugJsTranslations']),
            new TwigFunction('translation_grid_link', [$this, 'getTranslationGridLink'])
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
