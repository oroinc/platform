<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;
use Oro\Bundle\ActionBundle\DependencyInjection\OroActionExtension;

class OroActionExtensionTest extends ExtensionTestCase
{
    /**
     * @var array
     */
    protected $extensionConfigs = [];

    public function testLoad()
    {
        $this->loadExtension(new OroActionExtension());
        $expectedDefinitions = [
            // Services
            'oro_action.condition.route_exists',
            'oro_action.condition.service_exists',
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);
    }
}
