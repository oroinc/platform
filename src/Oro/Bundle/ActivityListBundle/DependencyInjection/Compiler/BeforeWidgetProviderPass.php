<?php

namespace Oro\Bundle\ActivityListBundle\DependencyInjection\Compiler;

use Oro\Bundle\UIBundle\DependencyInjection\Compiler\AbstractWidgetProviderPass;

class BeforeWidgetProviderPass extends AbstractWidgetProviderPass
{
    /**
     * {@inheritdoc}
     */
    protected function getChainProviderServiceId()
    {
        return 'oro_activity_list.widget_provider.before';
    }

    /**
     * {@inheritdoc}
     */
    protected function getProviderTagName()
    {
        return 'oro_activity_list.before_widget_provider';
    }
}
