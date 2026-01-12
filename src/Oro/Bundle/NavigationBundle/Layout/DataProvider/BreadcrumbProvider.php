<?php

namespace Oro\Bundle\NavigationBundle\Layout\DataProvider;

use Oro\Bundle\NavigationBundle\Menu\BreadcrumbManagerInterface;

/**
 * Provides breadcrumb navigation data for layout templates.
 *
 * This data provider retrieves breadcrumb information for a given menu, enabling the rendering of
 * breadcrumb navigation trails in layout templates. It acts as a bridge between the breadcrumb manager
 * and the layout system, making breadcrumb data accessible to frontend templates.
 */
class BreadcrumbProvider
{
    /**
     * @var BreadcrumbManagerInterface
     */
    private $breadcrumbManager;

    public function __construct(
        BreadcrumbManagerInterface $breadcrumbManager
    ) {
        $this->breadcrumbManager = $breadcrumbManager;
    }

    /**
     * Get breadcrumbs
     *
     * @param string $menuName
     *
     * @return array
     */
    public function getBreadcrumbs($menuName)
    {
        return $this->breadcrumbManager->getBreadcrumbs($menuName);
    }
}
