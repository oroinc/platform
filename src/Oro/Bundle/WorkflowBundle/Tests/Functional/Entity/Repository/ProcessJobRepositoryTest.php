<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Entity\Repository;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WorkflowBundle\Entity\ProcessJob;
use Oro\Bundle\WorkflowBundle\Entity\Repository\ProcessJobRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

/**
 * @dbIsolation
 * @dbReindex
 */
class ProcessJobRepositoryTest extends WebTestCase
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var ProcessJobRepository
     */
    protected $repository;

    /**
     * @var Registry
     */
    protected $registry;

    protected function setUp()
    {
        $this->initClient();

        $this->registry= $this->getContainer()->get('doctrine');

        $this->entityManager = $this->registry->getManagerForClass('OroWorkflowBundle:ProcessJob');
        $this->repository = $this->registry->getRepository('OroWorkflowBundle:ProcessJob');
    }

    public function testFindEntity()
    {
        $entityIdFirst  = 'first';
        $entityIdSecond = 'second';

        $user = $this->registry->getRepository('OroUserBundle:User')->find(1);

        $entityFirst = new WorkflowDefinition();
        $entityFirst
            ->setLabel('labelFirst')
            ->setName($entityIdFirst)
            ->setRelatedEntity($user)
            ->setEntityAttributeName('firstName');
        $this->entityManager->persist($entityFirst);

        $entitySecond = new WorkflowDefinition();
        $entitySecond
            ->setLabel('labelSecond')
            ->setName($entityIdSecond)
            ->setRelatedEntity($user)
            ->setEntityAttributeName('secondName');
        $this->entityManager->persist($entitySecond);

        $entityClass = get_class($entityFirst);

        $processJob = new ProcessJob();
        $processJob->setEntityId($entityIdFirst)->setEntityHash($entityClass . $entityIdFirst);
        $this->entityManager->persist($processJob);

        $this->entityManager->flush();

        $foundEntity = $this->repository->findEntity($processJob);
        $this->assertEquals($foundEntity, $entityFirst);
    }
}
