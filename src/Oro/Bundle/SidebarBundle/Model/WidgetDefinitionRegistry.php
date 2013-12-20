<?php

namespace Oro\Bundle\SidebarBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;

class WidgetDefinitionRegistry
{
    /**
     * @var ArrayCollection
     */
    protected $widgetDefinitions;

    /**
     * @param array $definitions
     */
    public function __construct(array $definitions)
    {
        $this->widgetDefinitions = new ArrayCollection();

        $this->setWidgetDefinitions($definitions);
    }

    /**
     * @param array $definitions
     */
    protected function setWidgetDefinitions(array $definitions)
    {
        foreach ($definitions as $name => $definition) {
            $this->widgetDefinitions->set($name, $definition);
        }
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
