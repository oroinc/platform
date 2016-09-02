<?php
namespace Oro\Bundle\IntegrationBundle\Tests\Functional\Command;

use Oro\Bundle\IntegrationBundle\Async\Topics;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Tests\Functional\DataFixtures\LoadChannelData;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Client\TraceableMessageProducer;

/**
 * @dbIsolationPerTest
 */
class ReversSyncCommandTest extends WebTestCase
{
    use MessageQueueExtension;

    public function setUp()
    {
        $this->initClient();
        $this->loadFixtures([LoadChannelData::class]);
    }

    public function testShouldOutputHelpForTheCommand()
    {
        $result = $this->runCommand('oro:integration:reverse:sync', ['--help']);

        $this->assertContains("Usage:\n  oro:integration:reverse:sync [options]", $result);
    }

    public function testShouldSendSyncIntegrationWithoutAnyAdditionalOptions()
    {
        /** @var Channel $integration */
        $integration = $this->getReference('oro_integration:foo_integration');

        $result = $this->runCommand('oro:integration:reverse:sync', ['--integration='.$integration->getId()]);

        $this->assertContains('Run revers sync for "Foo Integration" integration', $result);
        $this->assertContains('Completed', $result);

        $traces = $this->getMessageProducer()->getTopicSentMessages(Topics::REVERS_SYNC_INTEGRATION);

        $this->assertCount(1, $traces);

        $this->assertEquals([
            'integration_id' => $integration->getId(),
            'connector_parameters' => [ ],
            'connector' => null,
            'transport_batch_size' => 100,
        ], $traces[0]['message']->getBody());
        $this->assertEquals(MessagePriority::VERY_LOW, $traces[0]['message']->getPriority());
    }

    public function testShouldSendSyncIntegrationWithCustomConnectorAndOptions()
    {
        /** @var Channel $integration */
        $integration = $this->getReference('oro_integration:foo_integration');

        $result = $this->runCommand('oro:integration:reverse:sync', [
            '--integration='.$integration->getId(),
            '--connector' => 'theConnector',
            'fooConnectorOption=fooValue',
            'barConnectorOption=barValue',
        ]);

        $this->assertContains('Run revers sync for "Foo Integration" integration', $result);
        $this->assertContains('Completed', $result);

        $traces = $this->getMessageProducer()->getTopicSentMessages(Topics::REVERS_SYNC_INTEGRATION);

        $this->assertCount(1, $traces);

        $this->assertEquals([
            'integration_id' => $integration->getId(),
            'connector_parameters' => [
                'fooConnectorOption' => 'fooValue',
                'barConnectorOption' => 'barValue',
            ],
            'connector' => 'theConnector',
            'transport_batch_size' => 100,
        ], $traces[0]['message']->getBody());
        $this->assertEquals(MessagePriority::VERY_LOW, $traces[0]['message']->getPriority());
    }

    /**
     * @return TraceableMessageProducer
     */
    private function getMessageProducer()
    {
        return $this->getContainer()->get('oro_message_queue.message_producer');
    }
}
