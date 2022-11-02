<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\ActivityListBundle\Controller\Api\Rest\ActivityListController;
use Oro\Bundle\ActivityListBundle\DependencyInjection\OroActivityListExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroActivityListExtensionTest extends ExtensionTestCase
{
    public function testLoad(): void
    {
        $this->loadExtension(new OroActivityListExtension());

        $expectedDefinitions = [
            ActivityListController::class,
        ];

        $this->assertDefinitionsLoaded($expectedDefinitions);
    }
}
