<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\EntityConfigBundle\DependencyInjection\OroEntityConfigExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroEntityConfigExtensionTest extends ExtensionTestCase
{
    public function testExtension()
    {
        $extension = new OroEntityConfigExtension();

        $this->loadExtension($extension);

        $expectedDefinitions = [
            'oro_entity_config.importexport.configuration_provider.field_config_model',
            'oro_entity_config.importexport.configuration_provider.field_config_model_attribute',
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);
    }
}
