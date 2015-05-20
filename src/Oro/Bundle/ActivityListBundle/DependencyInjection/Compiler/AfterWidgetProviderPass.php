<?php

namespace Oro\Bundle\ActivityListBundle\DependencyInjection\Compiler;

use Oro\Bundle\UIBundle\DependencyInjection\Compiler\AbstractWidgetProviderPass;

class AfterWidgetProviderPass extends AbstractWidgetProviderPass
{
    /**
     * {@inheritdoc}
     */
    protected function getChainProviderServiceId()
    {
        return 'oro_activity_list.widget_provider.after';
    }

    /**
     * {@inheritdoc}
     */
    protected function getProviderTagName()
    {
        return 'oro_activity_list.after_widget_provider';
    }
}
