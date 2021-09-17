<?php

namespace Oro\Bundle\SoapBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\SoapBundle\DependencyInjection\OroSoapExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroSoapExtensionTest extends ExtensionTestCase
{
    public function testLoad(): void
    {
        $this->loadExtension(new OroSoapExtension());

        $expectedDefinitions = [
            'oro_soap.client.factory',
            'oro_soap.client',
            'oro_soap.client.factory.settings',
        ];

        $this->assertDefinitionsLoaded($expectedDefinitions);
    }
}
