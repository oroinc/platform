<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

abstract class AbstractVirtualRelationProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var WorkflowManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $workflowManager;

    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var WorkflowItemVirtualRelationProvider */
    protected $provider;

    /**
     * @param string $className
     * @param string $fieldName
     * @return array
     */
    abstract protected function getVirtualRelations($className, $fieldName);

    /**
     * @param string $className
     * @param string $fieldName
     * @return array
     */
    abstract protected function getVirtualRelationsQuery($className, $fieldName);

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
            ->method('hasApplicableWorkflowsByEntityClass')->willReturn(false);

        $this->assertFalse(
            $this->provider->isVirtualRelation('stdClass', $this->provider->getRelationName())
        );
    }

    public function testIsVirtualRelation()
    {
        $this->doctrineHelper->expects($this->never())->method('getSingleEntityIdentifierFieldName');

        $this->workflowManager->expects($this->once())
            ->method('hasApplicableWorkflowsByEntityClass')
            ->with('stdClass')
            ->willReturn(true);

        $this->assertTrue(
            $this->provider->isVirtualRelation('stdClass', $this->provider->getRelationName())
        );
    }

    // testGetVirtualRelations

    public function testGetVirtualRelationsAndNoApplicableWorkflows()
    {
        $this->doctrineHelper->expects($this->never())->method('getSingleEntityIdentifierFieldName');

        $this->workflowManager->expects($this->once())
            ->method('hasApplicableWorkflowsByEntityClass')->willReturn(false);

        $this->assertEquals([], $this->provider->getVirtualRelations('stdClass'));
    }

    public function testGetVirtualRelations()
    {
        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifierFieldName')
            ->with('stdClass')
            ->willReturn('id');

        $this->workflowManager->expects($this->once())
            ->method('hasApplicableWorkflowsByEntityClass')
            ->with('stdClass')
            ->willReturn(true);

        $this->assertEquals(
            $this->getVirtualRelations('stdClass', 'id'),
            $this->provider->getVirtualRelations('stdClass', 'id')
        );
    }

    // testGetVirtualRelationsQuery

    public function testGetVirtualRelationQueryAndNoApplicableWorkflows()
    {
        $this->doctrineHelper->expects($this->never())->method('getSingleEntityIdentifierFieldName');

        $this->workflowManager->expects($this->once())
            ->method('hasApplicableWorkflowsByEntityClass')->willReturn(false);

        $this->assertEquals([], $this->provider->getVirtualRelationQuery('stdClass', 'field1'));
    }

    public function testGetVirtualRelationQueryAndUnknownRelationFieldName()
    {
        $this->doctrineHelper->expects($this->once())->method('getSingleEntityIdentifierFieldName');

        $this->workflowManager->expects($this->once())
            ->method('hasApplicableWorkflowsByEntityClass')->willReturn(true);

        $this->assertEquals([], $this->provider->getVirtualRelationQuery('stdClass', 'unknown_field'));
    }

    public function testGetVirtualRelationQuery()
    {
        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifierFieldName')
            ->willReturn('id');

        $this->workflowManager->expects($this->once())
            ->method('hasApplicableWorkflowsByEntityClass')->willReturn(true);

        $this->assertEquals(
            $this->getVirtualRelationsQuery('stdClass', 'id'),
            $this->provider->getVirtualRelationQuery('stdClass', $this->provider->getRelationName())
        );
    }

    // testGetTargetJoinAlias

    public function testGetTargetJoinAlias()
    {
        $this->assertEquals($this->provider->getRelationName(), $this->provider->getTargetJoinAlias('', ''));
    }
}
