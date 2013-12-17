<?php

namespace Oro\Bundle\SidebarBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Templating\Asset\PackageInterface;

class WidgetDefinitionRegistry
{
    /**
     * @var ArrayCollection
     */
    protected $widgetDefinitions;

    /**
     * @var PackageInterface
     */
    protected $assetsHelper;

    /**
     * @param array $definitions
     * @param PackageInterface $assetHelper
     */
    public function __construct(array $definitions, PackageInterface $assetHelper)
    {
        $this->assetsHelper = $assetHelper;
        $this->widgetDefinitions = new ArrayCollection();

        $this->setWidgetDefinitions($definitions);
    }

    /**
     * @param array $definitions
     */
    protected function setWidgetDefinitions(array $definitions)
    {
        foreach ($definitions as $definition) {
            $definition['icon'] = $this->assetsHelper->getUrl($definition['icon']);
            $this->widgetDefinitions->add($definition);
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
