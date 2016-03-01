<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\UIBundle\DependencyInjection\Compiler\ViewActionWidgetProviderPass;

class ViewActionWidgetProviderPassTest extends ActionWidgetProviderPassAbstractTest
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

    /**
     * {@inheritdoc}
     */
    protected function createTestInstance()
    {
        return new ViewActionWidgetProviderPass();
    }
}
