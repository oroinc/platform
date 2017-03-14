<?php

namespace Oro\Bundle\NavigationBundle\Layout\DataProvider;

use Oro\Bundle\NavigationBundle\Menu\BreadcrumbManagerInterface;
use Oro\Component\DependencyInjection\ServiceLink;

class BreadcrumbProvider
{
    /**
     * @var ServiceLink
     */
    private $breadcrumbManagerLink;

    /**
     * @param ServiceLink $breadcrumbManagerLink
     */
    public function __construct(
        ServiceLink $breadcrumbManagerLink
    ) {
        $this->breadcrumbManagerLink = $breadcrumbManagerLink;
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
        /** @var BreadcrumbManagerInterface $breadcrumbManager */
        $breadcrumbManager = $this->breadcrumbManagerLink->getService();

        return $breadcrumbManager->getBreadcrumbs($menuName, false);
    }
}
