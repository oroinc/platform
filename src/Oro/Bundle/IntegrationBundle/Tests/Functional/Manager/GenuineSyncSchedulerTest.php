<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Functional\Manager;

use JMS\JobQueueBundle\Entity\Job;
use Oro\Bundle\IntegrationBundle\Command\SyncCommand;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Manager\GenuineSyncScheduler;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class GenuineSyncSchedulerTest extends WebTestCase
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

    public function testCouldBeGetFromContainerAsService()
    {
        $service = $this->getContainer()->get('oro_integration.genuine_sync_scheduler');

        $this->assertInstanceOf('Oro\Bundle\IntegrationBundle\Manager\GenuineSyncScheduler', $service);
    }

    public function testSchedule()
    {
        //guard
        $this->assertEmpty($this->getScheduledJobs(), 'Should be empty before test');

        $integration = new Integration();
        $this->writeIdProperty($integration, 123);

        /** @var GenuineSyncScheduler $service */
        $service = $this->getContainer()->get('oro_integration.genuine_sync_scheduler');

        $service->schedule($integration);

        $this->assertCount(1, $this->getScheduledJobs());
    }

    /**
     * @return array|Job[]
     */
    protected function getScheduledJobs()
    {
        return $this->getContainer()->get('doctrine')->getRepository('JMSJobQueueBundle:Job')
            ->findBy(['command' => SyncCommand::COMMAND_NAME]);
    }

    /**
     * @param object $object
     * @param int $id
     */
    protected function writeIdProperty($object, $id)
    {
        $rp = new \ReflectionProperty($object, 'id');
        $rp->setAccessible(true);
        $rp->setValue($object, $id);
        $rp->setAccessible(false);
    }
}
