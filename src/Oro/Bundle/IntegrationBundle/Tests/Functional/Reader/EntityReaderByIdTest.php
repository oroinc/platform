<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Functional\Reader;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Oro\Bundle\BatchBundle\Entity\JobExecution;
use Oro\Bundle\BatchBundle\Entity\JobInstance;
use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Reader\EntityReaderById;
use Oro\Bundle\IntegrationBundle\Tests\Functional\DataFixtures\LoadChannelData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class EntityReaderByIdTest extends WebTestCase
{
    /** @var EntityReaderById */
    private $reader;

    /** @var EntityManagerInterface */
    private $entityManager;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadChannelData::class]);

        $this->reader = $this->getContainer()->get('oro_integration.reader.entity.by_id');
        $this->entityManager = $this->getContainer()->get('doctrine')->getManagerForClass(Channel::class);
    }

    /**
     * This test covers issues with service initialization (some dependency not set or configured incorrectly)
     */
    public function testReaderProperlyInitialized()
    {
        $stubQuery = new Query($this->entityManager);
        $stubQuery->setDQL('SELECT c FROM OroIntegrationBundle:Channel c');

        $jobInstance = new JobInstance();
        $jobInstance->setRawConfiguration([
            'query' => $stubQuery
        ]);

        $jobExecution = new JobExecution();
        $jobExecution->setJobInstance($jobInstance);

        $this->reader->setStepExecution(new StepExecution('test', $jobExecution));

        // In case reader is not initialized properly (for example EventDispatcher is not set) we will have an exception
        // When it fails please check oro_integration.reader.entity.by_id service definition
        $this->reader->read();
    }
}
