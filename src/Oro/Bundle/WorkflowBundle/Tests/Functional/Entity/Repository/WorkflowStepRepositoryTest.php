<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowStepRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadWorkflowSteps;

class WorkflowStepRepositoryTest extends WebTestCase
{
    /**
     * @var WorkflowStepRepository
     */
    private $repository;

    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([LoadWorkflowSteps::class]);

        $this->repository = self::getContainer()->get('doctrine')->getRepository(WorkflowStep::class);
    }

    public function testFindByIds()
    {
        /**
         * @var WorkflowStep $firstStep
         * @var WorkflowStep $secondStep
         */
        $firstStep = $this->getReference(LoadWorkflowSteps::STEP_1);
        $secondStep = $this->getReference(LoadWorkflowSteps::STEP_2);

        $result = $this->repository->findByIds([$firstStep->getId(), $secondStep->getId(), 0]);
        $expectedResult = [
            $firstStep->getId() => $firstStep,
            $secondStep->getId() => $secondStep,
        ];
        $this->assertSame($expectedResult, $result);
    }
}
