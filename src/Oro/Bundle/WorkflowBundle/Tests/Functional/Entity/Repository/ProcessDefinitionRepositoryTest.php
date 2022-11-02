<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\Repository\ProcessDefinitionRepository;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadProcessEntities;

class ProcessDefinitionRepositoryTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadProcessEntities::class]);
    }

    private function getRepository(): ProcessDefinitionRepository
    {
        return self::getContainer()->get('doctrine')->getRepository(ProcessDefinition::class);
    }

    /**
     * @dataProvider findLikeNameDataProvider
     */
    public function testFindLikeName(string $name, int $expectedCount)
    {
        $this->assertCount($expectedCount, $this->getRepository()->findLikeName($name));
    }

    public function findLikeNameDataProvider(): array
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
