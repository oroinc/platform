<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\DataGridBundle\Controller\Api\Rest\GridViewController;
use Oro\Bundle\DataGridBundle\DependencyInjection\OroDataGridExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroDataGridExtensionTest extends ExtensionTestCase
{
    public function testLoad(): void
    {
        $this->loadExtension(new OroDataGridExtension());

        $expectedDefinitions = [
            GridViewController::class,
        ];

        $this->assertDefinitionsLoaded($expectedDefinitions);
    }
}
