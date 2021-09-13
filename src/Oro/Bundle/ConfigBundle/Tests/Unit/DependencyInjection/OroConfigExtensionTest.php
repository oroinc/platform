<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\ConfigBundle\Controller\Api\Rest\ConfigurationController;
use Oro\Bundle\ConfigBundle\DependencyInjection\OroConfigExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroConfigExtensionTest extends ExtensionTestCase
{
    public function testLoad(): void
    {
        $expectedDefinitions = [
            ConfigurationController::class,
        ];

        $this->loadExtension(new OroConfigExtension());
        $this->assertDefinitionsLoaded($expectedDefinitions);
    }
}
