<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Provider;

use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowDefinitionRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Provider\WorkflowVirtualRelationProvider;

class WorkflowVirtualRelationProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $doctrineHelper;

    /** @var Cache|\PHPUnit\Framework\MockObject\MockObject */
    protected $entitiesWithWorkflowCache;

    /** @var WorkflowVirtualRelationProvider */
    protected $provider;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->entitiesWithWorkflowCache = $this->createMock(Cache::class);

        $this->provider = new WorkflowVirtualRelationProvider(
            $this->doctrineHelper,
            $this->entitiesWithWorkflowCache
        );
    }

    // testIsVirtualRelation
    public function testIsVirtualRelationAndUnknownRelationFieldName()
    {
        $this->doctrineHelper->expects($this->never())->method('getSingleEntityIdentifierFieldName');
        $this->entitiesWithWorkflowCache->expects($this->never())
            ->method($this->anything());

        $this->assertFalse($this->provider->isVirtualRelation('stdClass', 'unknown_relation'));
    }

    public function testIsVirtualRelationAndNoApplicableWorkflows()
    {
        $this->doctrineHelper->expects($this->never())->method('getSingleEntityIdentifierFieldName');

        $this->entitiesWithWorkflowCache->expects($this->once())
            ->method('fetch')
            ->with(WorkflowVirtualRelationProvider::ENTITIES_WITH_WORKFLOW)
            ->willReturn([]);

        $this->assertFalse(
            $this->provider->isVirtualRelation('stdClass', WorkflowVirtualRelationProvider::ITEMS_RELATION_NAME)
        );
    }

    /**
     * @dataProvider fieldDataProvider
     * @param string $field
     */
    public function testIsVirtualRelationAndKnownRelation($field)
    {
        $this->doctrineHelper->expects($this->never())->method('getSingleEntityIdentifierFieldName');

        $class = 'stdClass';
        $this->assertGetEntitiesWithoutCacheCall($class);

        $this->assertTrue($this->provider->isVirtualRelation($class, $field));
    }

    /**
     * @return array
     */
    public function fieldDataProvider()
    {
        return [
            'item' => [WorkflowVirtualRelationProvider::ITEMS_RELATION_NAME],
            'step' => [WorkflowVirtualRelationProvider::STEPS_RELATION_NAME]
        ];
    }

    public function testGetVirtualRelationsAndNoApplicableWorkflows()
    {
        $this->doctrineHelper->expects($this->never())->method('getSingleEntityIdentifierFieldName');

        $this->entitiesWithWorkflowCache->expects($this->once())
            ->method('fetch')
            ->with(WorkflowVirtualRelationProvider::ENTITIES_WITH_WORKFLOW)
            ->willReturn([]);

        $this->assertEquals([], $this->provider->getVirtualRelations('stdClass'));
    }

    public function testGetVirtualRelationsCachedEntitiesWithWorkflows()
    {
        $this->doctrineHelper->expects($this->never())->method('getSingleEntityIdentifierFieldName');

        $this->entitiesWithWorkflowCache->expects($this->once())
            ->method('fetch')
            ->with(WorkflowVirtualRelationProvider::ENTITIES_WITH_WORKFLOW)
            ->willReturn(['stdClass' => true]);

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

    public function testGetVirtualRelationsNotCachedEntitiesWithWorkflows()
    {
        $className = 'stdClass';

        $this->doctrineHelper->expects($this->never())->method('getSingleEntityIdentifierFieldName');
        $this->assertGetEntitiesWithoutCacheCall($className);

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

        $this->assertGetEntitiesWithoutCacheCall();

        $this->assertEquals(
            [],
            $this->provider->getVirtualRelationQuery('stdClass', WorkflowVirtualRelationProvider::ITEMS_RELATION_NAME)
        );
    }

    public function testGetVirtualRelationQueryAndUnknownRelationFieldName()
    {
        $this->doctrineHelper->expects($this->never())->method('getSingleEntityIdentifierFieldName');
        $this->entitiesWithWorkflowCache->expects($this->never())
            ->method($this->anything());

        $this->assertEquals([], $this->provider->getVirtualRelationQuery('stdClass', 'unknown_field'));
    }

    public function testGetVirtualRelationQuery()
    {
        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifierFieldName')
            ->with('stdClass')
            ->willReturn('id');

        $this->entitiesWithWorkflowCache->expects($this->once())
            ->method('fetch')
            ->with(WorkflowVirtualRelationProvider::ENTITIES_WITH_WORKFLOW)
            ->willReturn(['stdClass' => true]);

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

    public function testGetTargetJoinAlias()
    {
        $this->assertEquals('virtual_relation', $this->provider->getTargetJoinAlias('', 'virtual_relation'));
    }

    /**
     * @param string|null $class
     */
    private function assertGetEntitiesWithoutCacheCall($class = null)
    {
        $classes = [];
        $expectedClasses = [];
        if ($class) {
            $classes[] = $class;
            $expectedClasses[$class] = true;
        }
        /** @var WorkflowDefinitionRepository|\PHPUnit\Framework\MockObject\MockObject $repo */
        $repo = $this->createMock(WorkflowDefinitionRepository::class);
        $repo->expects($this->once())
            ->method('getAllRelatedEntityClasses')
            ->with(true)
            ->willReturn($classes);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(WorkflowDefinition::class)
            ->willReturn($repo);

        $this->entitiesWithWorkflowCache->expects($this->once())
            ->method('fetch')
            ->with(WorkflowVirtualRelationProvider::ENTITIES_WITH_WORKFLOW)
            ->willReturn(false);
        $this->entitiesWithWorkflowCache->expects($this->once())
            ->method('save')
            ->with(WorkflowVirtualRelationProvider::ENTITIES_WITH_WORKFLOW, $expectedClasses);
    }
}
