<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Acl;

use Oro\Bundle\ActionBundle\Model\Attribute;
use Oro\Bundle\ActionBundle\Model\AttributeManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Acl\AclManager;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowEntityAcl;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowEntityAclIdentity;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowException;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;

class AclManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var WorkflowRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $workflowRegistry;

    /** @var AclManager */
    private $manager;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->workflowRegistry = $this->createMock(WorkflowRegistry::class);

        $this->manager = new AclManager($this->doctrineHelper, $this->workflowRegistry);
    }

    public function testUpdateAclIdentities()
    {
        $entity = new \DateTime();
        $entityIdentifier = 42;
        $workflowName = 'test_workflow';

        $firstStep = new WorkflowStep();
        $firstStep->setName('first_step');
        $secondStep = new WorkflowStep();
        $secondStep->setName('second_step');

        $firstAttribute = new Attribute();
        $firstAttribute->setName('first_attribute')->setOption('class', 'FirstTestEntity');
        $secondAttribute = new Attribute();
        $secondAttribute->setName('second_attribute')->setOption('class', 'SecondTestEntity');

        $firstEntityAcl = new WorkflowEntityAcl();
        $firstEntityAcl->setStep($firstStep)->setAttribute($firstAttribute->getName());
        $secondEntityAcl = new WorkflowEntityAcl();
        $secondEntityAcl->setStep($secondStep)->setAttribute($firstAttribute->getName());
        $thirdEntityAcl = new WorkflowEntityAcl();
        $thirdEntityAcl->setStep($secondStep)->setAttribute($secondAttribute->getName());

        $definition = new WorkflowDefinition();
        $definition->setName($workflowName)
            ->setSteps([$firstStep, $secondStep])
            ->setEntityAcls([$firstEntityAcl, $secondEntityAcl, $thirdEntityAcl]);

        $this->setWorkflow($workflowName, [$firstAttribute, $secondAttribute]);

        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->with($entity)
            ->willReturn($entityIdentifier);

        $workflowItem = new WorkflowItem();
        $workflowItem->setWorkflowName($workflowName)->setDefinition($definition)->setCurrentStep($secondStep);
        $workflowItem->getData()->set($firstAttribute->getName(), $entity);

        $this->assertEmpty($workflowItem->getAclIdentities()->toArray());
        $this->assertEquals($workflowItem, $this->manager->updateAclIdentities($workflowItem));
        $this->assertCount(1, $workflowItem->getAclIdentities());

        /** @var WorkflowEntityAclIdentity $aclIdentity */
        $aclIdentity = $workflowItem->getAclIdentities()->first();
        $this->assertEquals($secondEntityAcl, $aclIdentity->getAcl());
        $this->assertEquals($firstAttribute->getOption('class'), $aclIdentity->getEntityClass());
        $this->assertEquals($entityIdentifier, $aclIdentity->getEntityId());
        $this->assertEquals($workflowItem, $aclIdentity->getWorkflowItem());
    }

    public function testUpdateAclIdentitiesNotAnObjectException()
    {
        $this->expectException(WorkflowException::class);
        $this->expectExceptionMessage('Value of attribute "attribute" must be an object');

        $workflowName = 'test_workflow';

        $step = new WorkflowStep();
        $step->setName('step');

        $attribute = new Attribute();
        $attribute->setName('attribute')->setOption('class', 'TestEntity');

        $entityAcl = new WorkflowEntityAcl();
        $entityAcl->setStep($step)->setAttribute($attribute->getName());

        $definition = new WorkflowDefinition();
        $definition->setName($workflowName)->setSteps([$step])->setEntityAcls([$entityAcl]);

        $this->setWorkflow($workflowName, [$attribute]);

        $workflowItem = new WorkflowItem();
        $workflowItem->setWorkflowName($workflowName)->setDefinition($definition)->setCurrentStep($step);
        $workflowItem->getData()->set($attribute->getName(), 'not_an_object');

        $this->manager->updateAclIdentities($workflowItem);
    }

    private function setWorkflow(string $workflowName, array $attributes): void
    {
        $workflow = $this->createMock(Workflow::class);
        $workflow->expects($this->any())
            ->method('getAttributeManager')
            ->willReturn(new AttributeManager($attributes));

        $this->workflowRegistry->expects($this->any())
            ->method('getWorkflow')
            ->with($workflowName)
            ->willReturn($workflow);
    }
}
