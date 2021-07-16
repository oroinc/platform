<?php

namespace Oro\Bundle\TranslationBundle\Twig;

use Oro\Bundle\TranslationBundle\Helper\TranslationsDatagridRouteHelper;
use Psr\Container\ContainerInterface;
use Symfony\Component\Routing\RouterInterface;
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
    const NAME = 'oro_translation';

    /** @var ContainerInterface */
    private $container;

    /** @var bool */
    private $isDebugTranslator;

    /** @var bool */
    private $isDebugJsTranslations;

    public function __construct(ContainerInterface $container, bool $isDebugTranslator, bool $isDebugJsTranslations)
    {
        $this->container = $container;
        $this->isDebugTranslator = $isDebugTranslator;
        $this->isDebugJsTranslations = $isDebugJsTranslations;
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
        return $this->isDebugTranslator;
    }

    /**
     * @return bool
     */
    public function isDebugJsTranslations()
    {
        return $this->isDebugJsTranslations;
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

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_translation.helper.translation_route' => TranslationsDatagridRouteHelper::class,
        ];
    }
}
