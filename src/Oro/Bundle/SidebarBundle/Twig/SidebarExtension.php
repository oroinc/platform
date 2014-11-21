<?php

namespace Oro\Bundle\SidebarBundle\Twig;

use Symfony\Component\Templating\Asset\PackageInterface;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\SidebarBundle\Model\WidgetDefinitionRegistry;

class SidebarExtension extends \Twig_Extension
{
    const NAME = 'oro_sidebar';

    /**
     * @var WidgetDefinitionRegistry
     */
    protected $widgetDefinitionsRegistry;

    /**
     * @var PackageInterface
     */
    protected $assetHelper;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param WidgetDefinitionRegistry $widgetDefinitionsRegistry
     * @param PackageInterface $assetHelper
     * @param TranslatorInterface $translator
     */
    public function __construct(
        WidgetDefinitionRegistry $widgetDefinitionsRegistry,
        PackageInterface $assetHelper,
        TranslatorInterface $translator
    ) {
        $this->widgetDefinitionsRegistry = $widgetDefinitionsRegistry;
        $this->assetHelper = $assetHelper;
        $this->translator = $translator;
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
        $definitions = $this->widgetDefinitionsRegistry
            ->getWidgetDefinitionsByPlacement($placement)
            ->toArray();
        foreach ($definitions as &$definition) {
            $definition['icon'] = $this->assetHelper->getUrl($definition['icon']);
            $definition['title'] = $this->translator->trans($definition['title']);
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
