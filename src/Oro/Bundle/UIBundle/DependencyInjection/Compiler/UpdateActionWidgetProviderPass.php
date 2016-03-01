<?php

namespace Oro\Bundle\UIBundle\DependencyInjection\Compiler;

class UpdateActionWidgetProviderPass extends AbstractGroupingWidgetProviderPass
{
    /**
     * {@inheritdoc}
     */
    protected function getChainProviderServiceId()
    {
        return 'oro_ui.widget_provider.update_actions';
    }

    /**
     * {@inheritdoc}
     */
    protected function getProviderTagName()
    {
        return 'oro_ui.update_action_provider';
    }
}
