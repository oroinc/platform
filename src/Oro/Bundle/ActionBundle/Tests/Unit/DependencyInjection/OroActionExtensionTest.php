<?php
declare(strict_types=1);

namespace Oro\Bundle\ActionBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\ActionBundle\DependencyInjection\OroActionExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroActionExtensionTest extends ExtensionTestCase
{
    public function testLoad(): void
    {
        $this->loadExtension(new OroActionExtension());
        $expectedDefinitions = [
            // Services
            'oro_action.condition.service_exists',
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);
    }
}
