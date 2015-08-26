<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\UIBundle\DependencyInjection\Compiler\UpdateActionWidgetProviderPass;

class UpdateActionWidgetProviderPassTest extends ActionWidgetProviderPassAbstractTest
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

    /**
     * {@inheritdoc}
     */
    protected function createTestInstance()
    {
        return new UpdateActionWidgetProviderPass();
    }
}
