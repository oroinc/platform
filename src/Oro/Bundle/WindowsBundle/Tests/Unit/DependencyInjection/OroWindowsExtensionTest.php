<?php

namespace Oro\Bundle\WindowsBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;
use Oro\Bundle\WindowsBundle\Controller\Api\WindowsStateController;
use Oro\Bundle\WindowsBundle\DependencyInjection\OroWindowsExtension;

class OroWindowsExtensionTest extends ExtensionTestCase
{
    public function testLoad(): void
    {
        $this->loadExtension(new OroWindowsExtension());

        $expectedDefinitions = [
            WindowsStateController::class,
        ];

        $this->assertDefinitionsLoaded($expectedDefinitions);
    }
}
