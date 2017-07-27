<?php

namespace Oro\Bundle\NavigationBundle\Layout\DataProvider;

use Oro\Bundle\NavigationBundle\Menu\BreadcrumbManagerInterface;

class BreadcrumbProvider
{
    /**
     * @var BreadcrumbManagerInterface
     */
    private $breadcrumbManager;

    /**
     * @param BreadcrumbManagerInterface $breadcrumbManager
     */
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
