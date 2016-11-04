<?php

namespace Oro\Bundle\SidebarBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;

class WidgetDefinitionRegistry
{
    const SIDEBAR_WIDGET_FEATURE_NAME = 'sidebar_widgets';

    /**
     * @var ArrayCollection
     */
    protected $widgetDefinitions;

    /**
     * @param array $definitions
     */
    public function __construct(array $definitions, FeatureChecker $featureChecker)
    {
        $this->featureChecker = $featureChecker;
        $this->widgetDefinitions = new ArrayCollection();

        $this->setWidgetDefinitions($definitions);
    }

    /**
     * @param array $definitions
     */
    public function setWidgetDefinitions(array $definitions)
    {
        foreach ($definitions as $name => $definition) {
            $this->widgetDefinitions->set($name, $definition);
        }
    }

    /**
     * @return ArrayCollection
     */
    public function getWidgetDefinitions()
    {
        return $this->widgetDefinitions;
    }

    /**
     * @param string $placement
     * @return ArrayCollection
     */
    public function getWidgetDefinitionsByPlacement($placement)
    {
        return $this->widgetDefinitions->filter(
            function ($widgetDefinition) use ($placement) {
                return $widgetDefinition['placement'] === $placement
                    || $widgetDefinition['placement'] === 'both';
            }
        );
    }
}
