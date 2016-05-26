<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Model\TransitionSchedule;

use Doctrine\Common\Persistence\ObjectRepository;
use Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Model\TransitionSchedule\ItemsFetcher;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadWorkflowAwareEntities;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadWorkflowDefinitions;

/**
 * @dbIsolation
 */
class TransitionScheduleHelperTest extends WebTestCase
{
    /** @var WorkflowStep */
    private $step;

    /** @var WorkflowAwareEntity */
    private $entity;

    /** @var ItemsFetcher */
    private $helper;

    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([
            'Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadWorkflowAwareEntities',
        ]);

        /** @var WorkflowDefinition $definition */
        $definition = $this
            ->getRepositoryForClass('OroWorkflowBundle:WorkflowDefinition')
            ->find(LoadWorkflowDefinitions::WITH_START_STEP);
        $this->step = $definition->getStartStep();

        $this->entity = $this->getRepositoryForClass('OroTestFrameworkBundle:WorkflowAwareEntity')
            ->findOneBy([], ['id' => 'DESC']);
        $this->helper = $this->getContainer()->get('oro_workflow.transition_schedule.items_fetcher');
    }

    /**
     * @dataProvider getWorkflowItemIdsDataProvider
     *
     * @param string $entityClass
     * @param bool $withSteps
     * @param bool $withFilter
     * @param int $expectedCount
     */
    public function testFetch($entityClass, $withSteps, $withFilter, $expectedCount)
    {
        $steps = $withSteps ? [$this->step] : [];
        $filter = '';
        if ($withFilter) {
            $filter = 'e.id = ' . $this->entity->getId();
        }

        $ids = $this->helper->fetch($steps, $entityClass, $filter);

        $this->assertCount($expectedCount, $ids);
        if ($withFilter) {
            $this->assertEquals($ids[0], $this->entity->getWorkflowItem()->getId());
        }
    }

    /**
     * @return array
     */
    public function getWorkflowItemIdsDataProvider()
    {
        return [
            'no steps' => [
                'entityClass' => 'OroTestFrameworkBundle:WorkflowAwareEntity',
                'withSteps' => false,
                'withFilter' => false,
                'expectedCount' => 0,
            ],
            'without filter' => [
                'entityClass' => 'OroTestFrameworkBundle:WorkflowAwareEntity',
                'withSteps' => true,
                'withFilter' => false,
                'expectedCount' => LoadWorkflowAwareEntities::COUNT,
            ],
            'with filter' => [
                'entityClass' => 'OroTestFrameworkBundle:WorkflowAwareEntity',
                'withSteps' => true,
                'withFilter' => true,
                'expectedCount' => 1,
            ],
        ];
    }

    /**
     * @param string $entityClass
     * @return ObjectRepository
     */
    private function getRepositoryForClass($entityClass)
    {
        return $this->getContainer()->get('doctrine')
            ->getManagerForClass($entityClass)
            ->getRepository($entityClass);
    }
}
