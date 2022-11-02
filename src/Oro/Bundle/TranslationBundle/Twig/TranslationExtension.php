<?php

namespace Oro\Bundle\TranslationBundle\Twig;

use Oro\Bundle\TranslationBundle\Helper\TranslationsDatagridRouteHelper;
use Psr\Container\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides Twig functions to debug translations:
 *   - oro_translation_debug_translator
 *   - oro_translation_debug_js_translations
 *   - translation_grid_link
 */
class TranslationExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    private ContainerInterface $container;
    private bool $isDebugTranslator;
    private bool $isDebugJsTranslations;

    public function __construct(ContainerInterface $container, bool $isDebugTranslator, bool $isDebugJsTranslations)
    {
        $this->container = $container;
        $this->isDebugTranslator = $isDebugTranslator;
        $this->isDebugJsTranslations = $isDebugJsTranslations;
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

    public function isDebugTranslator(): bool
    {
        return $this->isDebugTranslator;
    }

    public function isDebugJsTranslations(): bool
    {
        return $this->isDebugJsTranslations;
    }

    /**
     * @param array $filters
     * @param int   $referenceType
     *
     * @return string
     */
    public function getTranslationGridLink(
        array $filters = [],
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ) {
        return $this->getTranslationsDatagridRouteHelper()->generate($filters, $referenceType);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_translation.helper.translation_route' => TranslationsDatagridRouteHelper::class,
        ];
    }

    private function getTranslationsDatagridRouteHelper(): TranslationsDatagridRouteHelper
    {
        return $this->container->get('oro_translation.helper.translation_route');
    }
}
