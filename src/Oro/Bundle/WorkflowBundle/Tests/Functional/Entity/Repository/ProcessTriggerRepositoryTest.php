<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Entity\Repository;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\Entity\Repository\ProcessTriggerRepository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 * @dbReindex
 */
class ProcessTriggerRepositoryTest extends WebTestCase
{
    /**
     * @var ProcessTriggerRepository
     */
    protected $repository;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    protected function setUp()
    {
        $this->initClient();

        $doctrine = $this->getContainer()->get('doctrine');

        $this->entityManager = $doctrine->getManagerForClass('OroWorkflowBundle:ProcessTrigger');
        $this->repository = $doctrine->getRepository('OroWorkflowBundle:ProcessTrigger');
    }

    public function testEqualTriggers()
    {
        $definition = new ProcessDefinition();
        $definition->setName('test')
            ->setLabel('Test');

        $this->entityManager->persist($definition);
        $this->entityManager->flush($definition);

        $existingTrigger = new ProcessTrigger();
        $existingTrigger->setDefinition($definition)
            ->setEvent(ProcessTrigger::EVENT_UPDATE)
            ->setField('name');

        $this->entityManager->persist($existingTrigger);
        $this->entityManager->flush($existingTrigger);

        // test equal (existing) trigger
        $equalTrigger = new ProcessTrigger();
        $equalTrigger->setDefinition($definition)
            ->setEvent(ProcessTrigger::EVENT_UPDATE)
            ->setField('name');

        $this->assertEquals($existingTrigger, $this->repository->findEqualTrigger($equalTrigger));
        $this->assertTrue($this->repository->isEqualTriggerExists($existingTrigger));

        // test not equal (not existing) trigger
        $notEqualTrigger = new ProcessTrigger();
        $notEqualTrigger->setDefinition($definition)
            ->setEvent(ProcessTrigger::EVENT_CREATE);

        $this->assertNull($this->repository->findEqualTrigger($notEqualTrigger));
        $this->assertFalse($this->repository->isEqualTriggerExists($notEqualTrigger));
    }
}
