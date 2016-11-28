<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Functional\Manager;

use Oro\Bundle\IntegrationBundle\Manager\GenuineSyncScheduler;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class GenuineSyncSchedulerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
    }

    public function testCouldBeGetFromContainerAsService()
    {
        $service = $this->getContainer()->get('oro_integration.genuine_sync_scheduler');

        $this->assertInstanceOf(GenuineSyncScheduler::class, $service);
    }
}
