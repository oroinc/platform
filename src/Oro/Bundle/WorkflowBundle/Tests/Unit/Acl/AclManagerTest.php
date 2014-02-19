<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Acl;

use Oro\Bundle\WorkflowBundle\Acl\AclManager;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowEntityAcl;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowEntityAclIdentity;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Model\Attribute;
use Oro\Bundle\WorkflowBundle\Model\AttributeManager;

class AclManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AclManager
     */
    protected $manager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $workflowRegistry;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->workflowRegistry = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry')
            ->disableOriginalConstructor()
            ->getMock();

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
            ->setSteps(array($firstStep, $secondStep))
            ->setEntityAcls(array($firstEntityAcl, $secondEntityAcl, $thirdEntityAcl));

        $this->setWorkflow($workflowName, array($firstAttribute, $secondAttribute));

        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->with($entity)
            ->will($this->returnValue($entityIdentifier));

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

    /**
     * @expectedException \Oro\Bundle\WorkflowBundle\Exception\WorkflowException
     * @expectedExceptionMessage Value of attribute "attribute" must be an object
     */
    public function testUpdateAclIdentitiesNotAnObjectException()
    {
        $workflowName = 'test_workflow';

        $step = new WorkflowStep();
        $step->setName('step');

        $attribute = new Attribute();
        $attribute->setName('attribute')->setOption('class', 'TestEntity');

        $entityAcl = new WorkflowEntityAcl();
        $entityAcl->setStep($step)->setAttribute($attribute->getName());

        $definition = new WorkflowDefinition();
        $definition->setName($workflowName)->setSteps(array($step))->setEntityAcls(array($entityAcl));

        $this->setWorkflow($workflowName, array($attribute));

        $workflowItem = new WorkflowItem();
        $workflowItem->setWorkflowName($workflowName)->setDefinition($definition)->setCurrentStep($step);
        $workflowItem->getData()->set($attribute->getName(), 'not_an_object');

        $this->manager->updateAclIdentities($workflowItem);
    }

    /**
     * @param string $workflowName
     * @param array $attributes
     */
    protected function setWorkflow($workflowName, array $attributes)
    {
        $workflow = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Workflow')
            ->disableOriginalConstructor()
            ->getMock();
        $workflow->expects($this->any())
            ->method('getAttributeManager')
            ->will($this->returnValue(new AttributeManager($attributes)));

        $this->workflowRegistry->expects($this->any())
            ->method('getWorkflow')
            ->with($workflowName)
            ->will($this->returnValue($workflow));
    }
}
