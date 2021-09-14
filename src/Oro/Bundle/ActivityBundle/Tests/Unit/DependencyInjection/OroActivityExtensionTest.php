<?php

namespace Oro\Bundle\ActivityBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\ActivityBundle\Controller\Api\Rest as Api;
use Oro\Bundle\ActivityBundle\DependencyInjection\OroActivityExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroActivityExtensionTest extends ExtensionTestCase
{
    public function testLoad(): void
    {
        $this->loadExtension(new OroActivityExtension());

        $expectedDefinitions = [
            Api\ActivityContextController::class,
            Api\ActivityController::class,
            Api\ActivityEntityController::class,
            Api\ActivitySearchController::class,
            Api\ActivityTargetController::class,
        ];

        $this->assertDefinitionsLoaded($expectedDefinitions);
    }
}
