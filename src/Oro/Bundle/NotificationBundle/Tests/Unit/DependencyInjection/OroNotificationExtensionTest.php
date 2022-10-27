<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\NotificationBundle\Controller\Api\Rest\EmailNotificationController;
use Oro\Bundle\NotificationBundle\DependencyInjection\OroNotificationExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroNotificationExtensionTest extends ExtensionTestCase
{
    public function testLoad(): void
    {
        $this->loadExtension(new OroNotificationExtension());

        $expectedDefinitions = [
            EmailNotificationController::class,
        ];

        $this->assertDefinitionsLoaded($expectedDefinitions);
    }
}
