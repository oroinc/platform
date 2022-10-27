<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\NavigationBundle\Controller\Api\NavigationItemController;
use Oro\Bundle\NavigationBundle\Controller\Api\PagestateController;
use Oro\Bundle\NavigationBundle\Controller\Api\ShortcutsController;
use Oro\Bundle\NavigationBundle\DependencyInjection\OroNavigationExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroNavigationExtensionTest extends ExtensionTestCase
{
    public function testLoadConfiguration(): void
    {
        $this->loadExtension(new OroNavigationExtension());
        $expectedDefinitions = [
            'oro_menu.configuration_builder',
            'oro_menu.twig.extension',
            'oro_navigation.title_config_reader',
            'oro_navigation.configuration.provider',
            NavigationItemController::class,
            PagestateController::class,
            ShortcutsController::class,
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);
    }
}
