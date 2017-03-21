<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\IntegrationBundle\DependencyInjection\OroIntegrationExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroIntegrationExtensionTest extends ExtensionTestCase
{
    /**
     * @var OroIntegrationExtension
     */
    protected $extension;

    protected function setUp()
    {
        $this->extension = new OroIntegrationExtension();
    }

    public function testLoad()
    {
        $this->loadExtension($this->extension);

        $expectedDefinitions = [
            'oro_integration.datagrid.action_configuration',
            'oro_integration.repository.channel',
            'oro_integration.action_handler.channel_delete',
            'oro_integration.action_handler.channel_disable',
            'oro_integration.action_handler.channel_enable',
            'oro_integration.action_handler.channel_error',
            'oro_integration.action_handler.decorator.channel_delete_dispatcher',
            'oro_integration.action_handler.decorator.channel_disable_dispatcher',
            'oro_integration.action_handler.decorator.channel_enable_dispatcher',
            'oro_integration.action_handler.decorator.channel_delete_transaction',
            'oro_integration.action_handler.decorator.channel_disable_transaction',
            'oro_integration.action_handler.decorator.channel_enable_transaction',
            'oro_integration.factory.event.channel_delete',
            'oro_integration.factory.event.channel_disable',
            'oro_integration.factory.event.channel_enable',
            'oro_integration.utils.edit_mode',
        ];

        $this->assertDefinitionsLoaded($expectedDefinitions);
    }
}
