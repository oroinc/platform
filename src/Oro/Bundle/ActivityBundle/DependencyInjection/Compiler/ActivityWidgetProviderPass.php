<?php

namespace Oro\Bundle\ActivityBundle\DependencyInjection\Compiler;

use Oro\Bundle\UIBundle\DependencyInjection\Compiler\AbstractWidgetProviderPass;

class ActivityWidgetProviderPass extends AbstractWidgetProviderPass
{
    /**
     * {@inheritdoc}
     */
    protected function getChainProviderServiceId()
    {
        return 'oro_activity.widget_provider.activities';
    }

    /**
     * {@inheritdoc}
     */
    protected function getProviderTagName()
    {
        return 'oro_activity.activity_widget_provider';
    }
}
