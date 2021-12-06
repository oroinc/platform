<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WorkflowBundle\Entity\Repository\TransitionEventTriggerRepository;
use Oro\Bundle\WorkflowBundle\Entity\TransitionEventTrigger;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadTransitionTriggers;

class TransitionEventTriggerRepositoryTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadTransitionTriggers::class]);
    }

    private function getRepository(): TransitionEventTriggerRepository
    {
        return self::getContainer()->get('doctrine')->getRepository(TransitionEventTrigger::class);
    }

    /**
     * @dataProvider findAllWithDefinitionsProvider
     */
    public function testFindAllWithDefinitions(?bool $enabled)
    {
        $triggers = $this->getRepository()->findAllWithDefinitions($enabled);

        $this->assertCount($this->getTriggersCount($enabled), $triggers);
    }

    public function findAllWithDefinitionsProvider(): array
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

    private function getTriggersCount(?bool $enabled): int
    {
        $queryBuilder = $this->getRepository()->createQueryBuilder('t')->select('COUNT(t.id) as countT');

        if (null !== $enabled) {
            $queryBuilder->innerJoin('t.workflowDefinition', 'd')
                ->andWhere('d.active = :enabled')
                ->setParameter('enabled', $enabled);
        }

        return (int)$queryBuilder->getQuery()->getSingleScalarResult();
    }
}
