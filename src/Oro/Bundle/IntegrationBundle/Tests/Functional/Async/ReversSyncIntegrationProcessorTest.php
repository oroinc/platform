<?php
namespace Oro\Bundle\IntegrationBundle\Tests\Functional\Async;

use Oro\Bundle\IntegrationBundle\Async\ReversSyncIntegrationProcessor;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class ReversSyncIntegrationProcessorTest extends WebTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->initClient();
    }

    public function testCouldBeGetFromContainerAsService()
    {
        $processor = $this->getContainer()->get('oro_integration.async.revers_sync_integration_processor');

        $this->assertInstanceOf(ReversSyncIntegrationProcessor::class, $processor);
    }
}
