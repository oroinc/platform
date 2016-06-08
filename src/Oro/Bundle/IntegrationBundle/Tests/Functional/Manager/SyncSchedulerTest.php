<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Functional\Manager;

use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use Oro\Bundle\IntegrationBundle\Tests\Unit\Fixture\TestIntegrationType;
use Oro\Bundle\IntegrationBundle\Tests\Unit\Fixture\TestTwoWayConnector;

class SyncSchedulerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->startTransaction();
    }

    protected function tearDown()
    {
        parent::tearDown();

        self::$loadedFixtures = [];
        $this->rollbackTransaction();
    }

    public function testScheduleWithoutFlush()
    {
        $this->assertEmpty($this->getScheduledJobs(), 'Should be empty before test');

        $registry = $this->getContainer()->get('oro_integration.manager.types_registry');

        $integrationType = uniqid('testIntegrationType', true);
        $connectorType   = uniqid('testConnectorType', true);

        $registry->addChannelType($integrationType, new TestIntegrationType());
        $registry->addConnectorType($connectorType, $integrationType, new TestTwoWayConnector());

        $integration = new Integration();
        $integration->setType($integrationType);

        $scheduler = $this->getContainer()->get('oro_integration.sync_scheduler');
        $scheduler->schedule($integration, $connectorType, [], false);
        $this->assertEmpty($this->getScheduledJobs(), 'Should be empty before flush');

        $this->getContainer()->get('doctrine')->getManager()->flush();

        $jobs = $this->getScheduledJobs();
        $this->assertNotEmpty($jobs);
    }

    public function testWithFlush()
    {
        $this->assertEmpty($this->getScheduledJobs(), 'Should be empty before test');

        $registry = $this->getContainer()->get('oro_integration.manager.types_registry');

        $integrationType = uniqid('testIntegrationType', true);
        $connectorType   = uniqid('testConnectorType', true);

        $registry->addChannelType($integrationType, new TestIntegrationType());
        $registry->addConnectorType($connectorType, $integrationType, new TestTwoWayConnector());

        $integration = new Integration();
        $integration->setType($integrationType);

        $this->getContainer()->get('oro_integration.sync_scheduler')->schedule($integration, $connectorType);

        $jobs = $this->getScheduledJobs();
        $this->assertNotEmpty($jobs);

        $this->getContainer()->get('oro_integration.sync_scheduler')->schedule($integration, $connectorType);
        $jobs = $this->getScheduledJobs();
        $this->assertCount(1, $jobs, 'Should check do look up for already scheduled pending jobs');
    }

    /**
     * @return array|\JMS\JobQueueBundle\Entity\Job[]
     */
    protected function getScheduledJobs()
    {
        return $this->getContainer()->get('doctrine')->getRepository('JMSJobQueueBundle:Job')
            ->findBy(['command' => 'oro:integration:reverse:sync']);
    }
}
