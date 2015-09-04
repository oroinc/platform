<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Menu;

use Oro\Bundle\NavigationBundle\Menu\BreadcrumbProviderInterface;
use Oro\Bundle\NavigationBundle\Menu\BreadcrumbManager;

class ChainBreadcrumbProviderStub extends BreadcrumbManager implements BreadcrumbProviderInterface
{
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @param null $route
     * @return bool
     */
    public function supports($route = null)
    {
        return true;
    }
}
