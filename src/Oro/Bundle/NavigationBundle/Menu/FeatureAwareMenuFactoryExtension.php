<?php

namespace Oro\Bundle\NavigationBundle\Menu;

class FeatureAwareMenuFactoryExtension extends AbstractFeatureAwareMenuFactoryExtension
{
    /**
     * @return int|null|object
     */
    protected function getScopeId()
    {
        return null;
    }
}
