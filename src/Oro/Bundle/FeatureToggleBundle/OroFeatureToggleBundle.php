<?php

namespace Oro\Bundle\FeatureToggleBundle;

use Oro\Bundle\FeatureToggleBundle\DependencyInjection\OroFeatureToggleExtension;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroFeatureToggleBundle extends Bundle
{
    /**
     * {@inheritDoc}
     */
    public function getContainerExtension()
    {
        return new OroFeatureToggleExtension();
    }
}
