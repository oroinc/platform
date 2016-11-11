<?php

namespace Oro\Bundle\SidebarBundle\Twig;

use Symfony\Component\Asset\Packages as AssetHelper;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Bundle\SidebarBundle\Model\WidgetDefinitionRegistry;

class SidebarExtension extends \Twig_Extension implements FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait;

    const NAME = 'oro_sidebar';

    /**
     * @var WidgetDefinitionRegistry
     */
    protected $widgetDefinitionsRegistry;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var AssetHelper
     */
    protected $assetHelper;

    /**
     * @param WidgetDefinitionRegistry $widgetDefinitionsRegistry
     * @param TranslatorInterface $translator
     * @param AssetHelper $assetHelper
     */
    public function __construct(
        WidgetDefinitionRegistry $widgetDefinitionsRegistry,
        TranslatorInterface $translator,
        AssetHelper $assetHelper
    ) {
        $this->widgetDefinitionsRegistry = $widgetDefinitionsRegistry;
        $this->translator = $translator;
        $this->assetHelper = $assetHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('oro_sidebar_get_available_widgets', array($this, 'getWidgetDefinitions')),
        );
    }

    /**
     * Get available widgets for placement.
     *
     * @param string $placement
     * @return array
     */
    public function getWidgetDefinitions($placement)
    {
        $definitions = $this->widgetDefinitionsRegistry
            ->getWidgetDefinitionsByPlacement($placement)
            ->toArray();

        foreach ($definitions as $name => &$definition) {
            if (!$this->isResourceEnabled($name, 'sidebar_widgets')) {
                unset($definitions[$name]);
                continue;
            }

            $definition['title'] = $this->translator->trans($definition['title']);
            if (!empty($definition['dialogIcon'])) {
                $definition['dialogIcon'] = $this->assetHelper->getUrl($definition['dialogIcon']);
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
