<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\ConsentBundle\DependencyInjection\OroConsentExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroConsentExtensionTest extends ExtensionTestCase
{
    public function testLoad()
    {
        $this->loadExtension(new OroConsentExtension());

        $expectedParameters = [];
        $this->assertParametersLoaded($expectedParameters);

        $expectedDefinitions = [];
        $this->assertDefinitionsLoaded($expectedDefinitions);

        $expectedExtensionConfigs = [];
        $this->assertExtensionConfigsLoaded($expectedExtensionConfigs);
    }

    public function testGetAlias()
    {
        $extension = new OroConsentExtension();
        $this->assertEquals('oro_consent', $extension->getAlias());
    }
}
