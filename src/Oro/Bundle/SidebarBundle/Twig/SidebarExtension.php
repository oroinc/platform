<?php

namespace Oro\Bundle\SidebarBundle\Twig;

use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Asset\Packages as AssetHelper;

use Oro\Bundle\SidebarBundle\Model\WidgetDefinitionRegistry;

class SidebarExtension extends \Twig_Extension
{
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

        foreach ($definitions as &$definition) {
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
