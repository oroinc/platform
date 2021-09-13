<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\DashboardBundle\Controller\Api\Rest\DashboardController;
use Oro\Bundle\DashboardBundle\Controller\Api\Rest\WidgetController;
use Oro\Bundle\DashboardBundle\DependencyInjection\OroDashboardExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroDashboardExtensionTest extends ExtensionTestCase
{
    public function testLoad(): void
    {
        $this->loadExtension(new OroDashboardExtension());

        $expectedDefinitions = [
            DashboardController::class,
            WidgetController::class,
        ];

        $this->assertDefinitionsLoaded($expectedDefinitions);
    }
}
