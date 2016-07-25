<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Persistence\ObjectRepository;

use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowGroup;
use Oro\Bundle\WorkflowBundle\Model\GroupAssembler;

use Oro\Component\Testing\Unit\EntityTrait;

class GroupAssemblerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var ObjectRepository */
    protected $repository;

    /** @var GroupAssembler */
    protected $assembler;

    protected function setUp()
    {
        $this->repository = $this->getMockBuilder(ObjectRepository::class)->getMock();
        $this->assembler = new GroupAssembler($this->repository);
    }

    /**
     * @dataProvider assembleDataProvider
     */
    public function testAssemble($configuration, Collection $expectedGroups)
    {
        $this->assertEquals($expectedGroups, $this->assembler->assemble($configuration));
    }

    public function assembleDataProvider()
    {
        /** @var WorkflowGroup $activeGroup */
        $activeGroup = $this->getEntity(WorkflowGroup::class, [
            'type' => WorkflowGroup::TYPE_EXCLUSIVE_ACTIVE,
            'name'=> 'test_group1',
        ]);

        /** @var WorkflowGroup $recordGroup1 */
        $recordGroup1 = $this->getEntity(WorkflowGroup::class, [
            'type' => WorkflowGroup::TYPE_EXCLUSIVE_RECORD,
            'name'=> 'test_group2',
        ]);

        /** @var WorkflowGroup $recordGroup2 */
        $recordGroup2 = $this->getEntity(WorkflowGroup::class, [
            'type' => WorkflowGroup::TYPE_EXCLUSIVE_RECORD,
            'name'=> 'test_group1',
        ]);


        return [
            'empty' => [
                [],
                new ArrayCollection(),
            ],
            'only active' => [
                [WorkflowConfiguration::NODE_EXCLUSIVE_ACTIVE_GROUPS => [$activeGroup->getName()]],
                new ArrayCollection([$activeGroup]),
            ],
            'both' => [
                [
                    WorkflowConfiguration::NODE_EXCLUSIVE_ACTIVE_GROUPS => [$activeGroup->getName()],
                    WorkflowConfiguration::NODE_EXCLUSIVE_RECORD_GROUPS => [$recordGroup1->getName()],
                ],
                new ArrayCollection([$activeGroup, $recordGroup1]),
            ],
            'same names' => [
                [
                    WorkflowConfiguration::NODE_EXCLUSIVE_ACTIVE_GROUPS => [$activeGroup->getName()],
                    WorkflowConfiguration::NODE_EXCLUSIVE_RECORD_GROUPS => [$recordGroup2->getName()],
                ],
                new ArrayCollection([$activeGroup, $recordGroup2]),
            ],
        ];
    }
}
