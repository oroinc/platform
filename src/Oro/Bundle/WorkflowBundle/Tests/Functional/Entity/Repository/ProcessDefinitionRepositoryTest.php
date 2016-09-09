<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\Repository\ProcessDefinitionRepository;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadProcessEntities;

/**
 * @dbIsolation
 */
class ProcessDefinitionRepositoryTest extends WebTestCase
{
    /**
     * @var ProcessDefinitionRepository
     */
    protected $repository;

    protected function setUp()
    {
        $this->initClient();

        $this->repository = $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroWorkflowBundle:ProcessDefinition')
            ->getRepository('OroWorkflowBundle:ProcessDefinition');

        $this->loadFixtures(['Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadProcessEntities']);
    }

    /**
     * @param string $name
     * @param int $expectedCount
     *
     * @dataProvider findLikeNameDataProvider
     */
    public function testFindLikeName($name, $expectedCount)
    {
        $this->assertCount($expectedCount, $this->repository->findLikeName($name));
    }

    /**
     * @return array
     */
    public function findLikeNameDataProvider()
    {
        return [
            'empty_name' => [
                'name' => '',
                'expectedCount' => 0,
            ],
            'full_name' => [
                'name' => LoadProcessEntities::FIRST_DEFINITION,
                'expectedCount' => 1,
            ],
            'part_name' => [
                'name' => 'firs%',
                'expectedCount' => 1,
            ],
            'not_existing_name' => [
                'name' => 'not_existing_name',
                'expectedCount' => 0,
            ],
        ];
    }
}
