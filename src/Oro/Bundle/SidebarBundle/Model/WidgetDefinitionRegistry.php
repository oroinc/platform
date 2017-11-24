<?php

namespace Oro\Bundle\SidebarBundle\Model;

class WidgetDefinitionRegistry
{
    /** @var array */
    private $definitions;

    /**
     * @param array $definitions
     */
    public function __construct(array $definitions)
    {
        $this->definitions = $definitions;
    }

    /**
     * @param array $definitions [widget name => widget definition, ...]
     */
    public function setWidgetDefinitions(array $definitions)
    {
        foreach ($definitions as $name => $definition) {
            $this->definitions[$name] = $definition;
        }
    }

    /**
     * @return array [widget name => widget definition, ...]
     */
    public function getWidgetDefinitions()
    {
        return $this->definitions;
    }

    /**
     * @param string $placement
     *
     * @return array [widget name => widget definition, ...]
     */
    public function getWidgetDefinitionsByPlacement($placement)
    {
        $result = [];
        foreach ($this->definitions as $name => $definition) {
            if ($definition['placement'] === $placement || $definition['placement'] === 'both') {
                $result[$name] = $definition;
            }
        }

        return $result;
    }
}
