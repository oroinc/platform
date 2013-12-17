<?php

namespace Oro\Bundle\SidebarBundle\Twig;

use Oro\Bundle\SidebarBundle\Model\WidgetDefinitionRegistry;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SidebarExtension extends \Twig_Extension
{
    const NAME = 'oro_sidebar';

    /**
     * @var WidgetDefinitionRegistry
     */
    protected $widgetDefinitionsRegistry;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->widgetDefinitionsRegistry = $container->get('oro_sidebar.widget_definition.registry');
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
        return $this->widgetDefinitionsRegistry
            ->getWidgetDefinitionsByPlacement($placement)
            ->toArray();
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
