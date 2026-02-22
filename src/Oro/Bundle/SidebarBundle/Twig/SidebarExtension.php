<?php

namespace Oro\Bundle\SidebarBundle\Twig;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Bundle\SidebarBundle\Configuration\WidgetDefinitionProvider;
use Psr\Container\ContainerInterface;
use Symfony\Component\Asset\Packages as AssetHelper;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides a Twig function to retrieve sidebar widgets information:
 *   - oro_sidebar_get_available_widgets - Gets available widgets for the given placement.
 */
class SidebarExtension extends AbstractExtension implements FeatureToggleableInterface, ServiceSubscriberInterface
{
    use FeatureCheckerHolderTrait;

    public function __construct(
        private readonly ContainerInterface $container
    ) {
    }

    #[\Override]
    public function getFunctions()
    {
        return [
            new TwigFunction('oro_sidebar_get_available_widgets', [$this, 'getWidgetDefinitions']),
        ];
    }

    public function getWidgetDefinitions(string $placement): array
    {
        $definitions = $this->getWidgetDefinitionProvider()
            ->getWidgetDefinitionsByPlacement($placement);
        $translator = $this->getTranslator();
        $assetHelper = $this->getAssetHelper();

        foreach ($definitions as $name => &$definition) {
            if (!$this->featureChecker->isResourceEnabled($name, 'sidebar_widgets')) {
                unset($definitions[$name]);
                continue;
            }

            $definition['title'] = isset($definition['title']) ? $translator->trans((string) $definition['title']) : '';
            if (!empty($definition['icon'])) {
                $definition['icon'] = $assetHelper->getUrl($definition['icon']);
            }
            if (!empty($definition['dialogIcon'])) {
                $definition['dialogIcon'] = $assetHelper->getUrl($definition['dialogIcon']);
            }
        }

        return $definitions;
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            WidgetDefinitionProvider::class,
            TranslatorInterface::class,
            AssetHelper::class
        ];
    }

    private function getWidgetDefinitionProvider(): WidgetDefinitionProvider
    {
        return $this->container->get(WidgetDefinitionProvider::class);
    }

    private function getTranslator(): TranslatorInterface
    {
        return $this->container->get(TranslatorInterface::class);
    }

    private function getAssetHelper(): AssetHelper
    {
        return $this->container->get(AssetHelper::class);
    }
}
