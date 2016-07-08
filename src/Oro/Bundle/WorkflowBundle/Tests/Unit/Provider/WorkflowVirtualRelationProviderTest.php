<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Provider;

use Doctrine\ORM\Query\Expr\Join;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Provider\WorkflowVirtualRelationProvider;

class WorkflowVirtualRelationProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var WorkflowManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $workflowManager;

    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var WorkflowVirtualRelationProvider */
    protected $provider;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->workflowManager = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\WorkflowManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new WorkflowVirtualRelationProvider($this->workflowManager, $this->doctrineHelper);
    }

    // testIsVirtualRelation
    public function testIsVirtualRelationAndUnknownRelationFieldName()
    {
        $this->doctrineHelper->expects($this->never())->method('getSingleEntityIdentifierFieldName');
        $this->workflowManager->expects($this->never())->method('hasApplicableWorkflowsByEntityClass');

        $this->assertFalse($this->provider->isVirtualRelation('stdClass', 'unknown_relation'));
    }

    public function testIsVirtualRelationAndNoApplicableWorkflows()
    {
        $this->doctrineHelper->expects($this->never())->method('getSingleEntityIdentifierFieldName');

        $this->workflowManager->expects($this->once())
            ->method('hasApplicableWorkflows')->willReturn(false);

        $this->assertFalse(
            $this->provider->isVirtualRelation('stdClass', WorkflowVirtualRelationProvider::ITEMS_RELATION_NAME)
        );
    }

    public function testIsVirtualRelationAndItemsRelation()
    {
        $this->doctrineHelper->expects($this->never())->method('getSingleEntityIdentifierFieldName');

        $this->workflowManager->expects($this->once())
            ->method('hasApplicableWorkflows')
            ->with('stdClass')
            ->willReturn(true);

        $this->assertTrue(
            $this->provider->isVirtualRelation('stdClass', WorkflowVirtualRelationProvider::ITEMS_RELATION_NAME)
        );
    }

    public function testIsVirtualRelationAndStepsRelation()
    {
        $this->doctrineHelper->expects($this->never())->method('getSingleEntityIdentifierFieldName');

        $this->workflowManager->expects($this->once())
            ->method('hasApplicableWorkflows')
            ->with('stdClass')
            ->willReturn(true);

        $this->assertTrue(
            $this->provider->isVirtualRelation('stdClass', WorkflowVirtualRelationProvider::STEPS_RELATION_NAME)
        );
    }

    public function testGetVirtualRelationsAndNoApplicableWorkflows()
    {
        $this->doctrineHelper->expects($this->never())->method('getSingleEntityIdentifierFieldName');

        $this->workflowManager->expects($this->once())
            ->method('hasApplicableWorkflows')->willReturn(false);

        $this->assertEquals([], $this->provider->getVirtualRelations('stdClass'));
    }

    public function testGetVirtualRelations()
    {
        $this->doctrineHelper->expects($this->never())->method('getSingleEntityIdentifierFieldName');

        $this->workflowManager->expects($this->once())
            ->method('hasApplicableWorkflows')
            ->with('stdClass')
            ->willReturn(true);

        $this->assertEquals(
            [
                WorkflowVirtualRelationProvider::ITEMS_RELATION_NAME => [
                    'label' => 'oro.workflow.workflowitem.entity_label',
                    'relation_type' => 'OneToMany',
                    'related_entity_name' => 'Oro\Bundle\WorkflowBundle\Entity\WorkflowItem',
                ],
                WorkflowVirtualRelationProvider::STEPS_RELATION_NAME => [
                    'label' => 'oro.workflow.workflowstep.entity_label',
                    'relation_type' => 'OneToMany',
                    'related_entity_name' => 'Oro\Bundle\WorkflowBundle\Entity\WorkflowStep',
                ],
            ],
            $this->provider->getVirtualRelations('stdClass')
        );
    }

    // testGetVirtualRelationsQuery
    public function testGetVirtualRelationQueryAndNoApplicableWorkflows()
    {
        $this->doctrineHelper->expects($this->never())->method('getSingleEntityIdentifierFieldName');

        $this->workflowManager->expects($this->once())
            ->method('hasApplicableWorkflows')->willReturn(false);

        $this->assertEquals(
            [],
            $this->provider->getVirtualRelationQuery('stdClass', WorkflowVirtualRelationProvider::ITEMS_RELATION_NAME)
        );
    }

    public function testGetVirtualRelationQueryAndUnknownRelationFieldName()
    {
        $this->doctrineHelper->expects($this->never())->method('getSingleEntityIdentifierFieldName');
        $this->workflowManager->expects($this->never())->method('hasApplicableWorkflows');

        $this->assertEquals([], $this->provider->getVirtualRelationQuery('stdClass', 'unknown_field'));
    }

    public function testGetVirtualRelationQuery()
    {
        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifierFieldName')
            ->with('stdClass')
            ->willReturn('id');

        $this->workflowManager->expects($this->once())
            ->method('hasApplicableWorkflows')->willReturn(true);

        $this->assertEquals(
            [
                'join' => [
                    'left' => [
                        [
                            'join' => WorkflowItem::class,
                            'alias' => WorkflowVirtualRelationProvider::ITEMS_RELATION_NAME,
                            'conditionType' => Join::WITH,
                            'condition' => sprintf(
                                'CAST(entity.%s as string) = CAST(%s.entityId as string) AND %s.entityClass = \'%s\'',
                                'id',
                                WorkflowVirtualRelationProvider::ITEMS_RELATION_NAME,
                                WorkflowVirtualRelationProvider::ITEMS_RELATION_NAME,
                                'stdClass'
                            )
                        ],
                        [
                            'join' => sprintf('%s.currentStep', WorkflowVirtualRelationProvider::ITEMS_RELATION_NAME),
                            'alias' => WorkflowVirtualRelationProvider::STEPS_RELATION_NAME,
                        ]
                    ]
                ]
            ],
            $this->provider->getVirtualRelationQuery('stdClass', WorkflowVirtualRelationProvider::ITEMS_RELATION_NAME)
        );
    }

    // testGetTargetJoinAlias
    public function testGetTargetJoinAlias()
    {
        $this->assertEquals('virtual_relation', $this->provider->getTargetJoinAlias('', 'virtual_relation'));
    }
}
