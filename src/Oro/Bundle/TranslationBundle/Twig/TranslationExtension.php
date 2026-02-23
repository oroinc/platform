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
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly bool $isDebugTranslator,
        private readonly bool $isDebugJsTranslations
    ) {
    }

    #[\Override]
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

    public function getTranslationGridLink(
        array $filters = [],
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ): string {
        return $this->getTranslationsDatagridRouteHelper()->generate($filters, $referenceType);
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            TranslationsDatagridRouteHelper::class
        ];
    }

    private function getTranslationsDatagridRouteHelper(): TranslationsDatagridRouteHelper
    {
        return $this->container->get(TranslationsDatagridRouteHelper::class);
    }
}
