<?php

namespace Oro\Bundle\UIBundle\DependencyInjection\Compiler;

class ViewActionWidgetProviderPass extends AbstractGroupingWidgetProviderPass
{
    /**
     * {@inheritdoc}
     */
    protected function getChainProviderServiceId()
    {
        return 'oro_ui.widget_provider.view_actions';
    }

    /**
     * {@inheritdoc}
     */
    protected function getProviderTagName()
    {
        return 'oro_ui.view_action_provider';
    }
}
