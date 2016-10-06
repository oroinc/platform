<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Functional\Manager;

use Oro\Bundle\IntegrationBundle\Manager\SyncScheduler;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class SyncSchedulerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
    }

    public function testCouldBeGetFromContainerAsService()
    {
        $service = $this->getContainer()->get('oro_integration.sync_scheduler');

        $this->assertInstanceOf(SyncScheduler::class, $service);
    }
}
