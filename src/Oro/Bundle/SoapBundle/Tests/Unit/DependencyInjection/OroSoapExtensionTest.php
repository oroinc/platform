<?php

namespace Oro\Bundle\SoapBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\SoapBundle\DependencyInjection\OroSoapExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroSoapExtensionTest extends ExtensionTestCase
{
    /**
     * @var OroSoapExtension
     */
    private $extension;

    protected function setUp()
    {
        $this->extension = new OroSoapExtension();
    }

    public function testLoad()
    {
        $this->loadExtension($this->extension);

        $expectedDefinitions = [
            'oro_soap.client.factory',
            'oro_soap.client',
            'oro_soap.client.factory.settings',
        ];

        $this->assertDefinitionsLoaded($expectedDefinitions);

        $expectedParameters = [

        ];

        $this->assertParametersLoaded($expectedParameters);
    }
}
