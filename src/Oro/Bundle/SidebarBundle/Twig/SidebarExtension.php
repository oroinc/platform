<?php

namespace Oro\Bundle\SidebarBundle\Twig;

use Oro\Bundle\SidebarBundle\Model\WidgetDefinitionRegistry;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Templating\Asset\PackageInterface;

class SidebarExtension extends \Twig_Extension
{
    const NAME = 'oro_sidebar';

    /**
     * @var WidgetDefinitionRegistry
     */
    protected $widgetDefinitionsRegistry;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @param WidgetDefinitionRegistry $widgetDefinitionsRegistry
     * @param ContainerInterface $container
     */
    public function __construct(WidgetDefinitionRegistry $widgetDefinitionsRegistry, ContainerInterface $container)
    {
        $this->widgetDefinitionsRegistry = $widgetDefinitionsRegistry;
        $this->container = $container;
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
        /** @var PackageInterface $assetHelper */
        $assetHelper =$this->container->get('templating.helper.assets');
        $definitions = $this->widgetDefinitionsRegistry
            ->getWidgetDefinitionsByPlacement($placement)
            ->toArray();
        foreach ($definitions as &$definition) {
            $definition['icon'] = $assetHelper->getUrl($definition['icon']);
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
