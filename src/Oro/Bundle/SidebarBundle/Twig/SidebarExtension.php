<?php

namespace Oro\Bundle\SidebarBundle\Twig;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Bundle\SidebarBundle\Model\WidgetDefinitionRegistry;
use Symfony\Component\Asset\Packages as AssetHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Translation\TranslatorInterface;

class SidebarExtension extends \Twig_Extension implements FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait;

    const NAME = 'oro_sidebar';

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
     * @return WidgetDefinitionRegistry
     */
    protected function getWidgetDefinitionRegistry()
    {
        return $this->container->get('oro_sidebar.widget_definition.registry');
    }

    /**
     * @return TranslatorInterface
     */
    protected function getTranslator()
    {
        return $this->container->get('translator');
    }

    /**
     * @return AssetHelper
     */
    protected function getAssetHelper()
    {
        return $this->container->get('assets.packages');
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('oro_sidebar_get_available_widgets', [$this, 'getWidgetDefinitions']),
        ];
    }

    /**
     * Get available widgets for placement.
     *
     * @param string $placement
     * @return array
     */
    public function getWidgetDefinitions($placement)
    {
        $definitions = $this->getWidgetDefinitionRegistry()
            ->getWidgetDefinitionsByPlacement($placement);
        $translator = $this->getTranslator();
        $assetHelper = $this->getAssetHelper();

        foreach ($definitions as $name => &$definition) {
            if (!$this->featureChecker->isResourceEnabled($name, 'sidebar_widgets')) {
                unset($definitions[$name]);
                continue;
            }

            $definition['title'] = $translator->trans($definition['title']);
            if (!empty($definition['icon'])) {
                $definition['icon'] = $assetHelper->getUrl($definition['icon']);
            }
            if (!empty($definition['dialogIcon'])) {
                $definition['dialogIcon'] = $assetHelper->getUrl($definition['dialogIcon']);
            }
        }

        return $definitions;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
