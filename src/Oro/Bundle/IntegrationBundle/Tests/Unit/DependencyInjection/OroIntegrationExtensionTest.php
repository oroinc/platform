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
        ];

        $this->assertDefinitionsLoaded($expectedDefinitions);
    }
}
