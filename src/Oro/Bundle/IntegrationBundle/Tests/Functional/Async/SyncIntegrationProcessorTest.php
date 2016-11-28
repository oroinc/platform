<?php
namespace Oro\Bundle\IntegrationBundle\Tests\Functional\Async;

use Oro\Bundle\IntegrationBundle\Async\SyncIntegrationProcessor;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class SyncIntegrationProcessorTest extends WebTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->initClient();
    }

    public function testCouldBeGetFromContainerAsService()
    {
        $processor = $this->getContainer()->get('oro_integration.async.sync_integration_processor');

        $this->assertInstanceOf(SyncIntegrationProcessor::class, $processor);
    }
}
