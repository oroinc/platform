<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Entity\Repository;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WorkflowBundle\Entity\Repository\TransitionEventTriggerRepository;
use Oro\Bundle\WorkflowBundle\Entity\TransitionEventTrigger;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadTransitionTriggers;

/**
 * @dbIsolation
 */
class TransitionEventTriggerRepositoryTest extends WebTestCase
{
    /** @var TransitionEventTriggerRepository */
    protected $repository;

    /** @var EntityManager */
    protected $entityManager;

    protected function setUp()
    {
        $this->initClient();

        $doctrine = $this->getContainer()->get('doctrine');
        $this->loadFixtures(
            [
                LoadTransitionTriggers::class
            ]
        );

        $this->entityManager = $doctrine->getManager();
        $this->repository = $doctrine->getRepository(TransitionEventTrigger::class);
    }

    /**
     * @dataProvider findAllWithDefinitionsProvider
     *
     * @param bool|null $enabled
     */
    public function testFindAllWithDefinitions($enabled)
    {
        $triggers = $this->repository->findAllWithDefinitions($enabled);

        $this->assertCount($this->getTriggersCount($enabled), $triggers);
    }

    /**
     * @return array
     */
    public function findAllWithDefinitionsProvider()
    {
        return [
            'only active workflow definitions' => [
                'enabled' => true
            ],
            'only inactive workflow definitions' => [
                'enabled' => false
            ],
            'all workflow definitions' => [
                'enabled' => null
            ]
        ];
    }

    /**
     * @param bool|null $enabled
     * @return int
     */
    protected function getTriggersCount($enabled = null)
    {
        $queryBuilder = $this->repository->createQueryBuilder('t')->select('COUNT(t.id) as countT');

        if (null !== $enabled) {
            $queryBuilder->innerJoin('t.workflowDefinition', 'd')
                ->andWhere('d.active = :enabled')
                ->setParameter('enabled', $enabled);
        }

        return (int)$queryBuilder->getQuery()->getSingleScalarResult();
    }
}
