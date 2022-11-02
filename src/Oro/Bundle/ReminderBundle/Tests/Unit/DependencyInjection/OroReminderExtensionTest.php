<?php

namespace Oro\Bundle\ReminderBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\ReminderBundle\Controller\Api\Rest\ReminderController;
use Oro\Bundle\ReminderBundle\DependencyInjection\OroReminderExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroReminderExtensionTest extends ExtensionTestCase
{
    public function testLoad(): void
    {
        $this->loadExtension(new OroReminderExtension());

        $expectedDefinitions = [
            ReminderController::class,
        ];

        $this->assertDefinitionsLoaded($expectedDefinitions);
    }
}
