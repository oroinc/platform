<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\NavigationBundle\DependencyInjection\OroNavigationExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroNavigationExtensionTest extends ExtensionTestCase
{
    /**
     * @var OroNavigationExtension
     */
    protected $extension;

    protected function setUp()
    {
        $this->extension = new OroNavigationExtension();
    }

    public function testLoadConfiguration()
    {
        $this->loadExtension(new OroNavigationExtension());
        $expectedDefinitions = [
            'oro_menu.configuration_builder',
            'oro_menu.twig.extension',
            'oro_navigation.title_config_reader',
            'oro_navigation.configuration.provider'
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);
    }
}
