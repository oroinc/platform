<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Entity;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowEntityAcl;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowEntityAclIdentity;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowRestriction;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowRestrictionIdentity;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowTransitionRecord;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class WorkflowItemTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var WorkflowItem
     */
    protected $workflowItem;

    protected function setUp()
    {
        $this->workflowItem = new WorkflowItem();
    }

    protected function tearDown()
    {
        unset($this->workflowItem);
    }

    public function testId()
    {
        $this->assertNull($this->workflowItem->getId());
        $value = 1;
        $this->workflowItem->setId($value);
        $this->assertEquals($value, $this->workflowItem->getId());
    }

    public function testWorkflowName()
    {
        $this->assertNull($this->workflowItem->getWorkflowName());
        $value = 'example_workflow';
        $this->workflowItem->setWorkflowName($value);
        $this->assertEquals($value, $this->workflowItem->getWorkflowName());
    }

    public function testCurrentStepName()
    {
        $this->assertNull($this->workflowItem->getCurrentStep());
        $value = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowStep')
            ->disableOriginalConstructor()
            ->getMock();
        $this->workflowItem->setCurrentStep($value);
        $this->assertEquals($value, $this->workflowItem->getCurrentStep());
    }

    public function testData()
    {
        $this->assertInstanceOf('Oro\Bundle\WorkflowBundle\Model\WorkflowData', $this->workflowItem->getData());

        $data = new WorkflowData();
        $data['foo'] = 'Bar';

        $this->workflowItem->setData($data);
        $this->assertEquals($data, $this->workflowItem->getData());
    }

    public function testGetDataWithSerialization()
    {
        $definition = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition')
            ->disableOriginalConstructor()
            ->getMock();
        $definition->expects($this->once())
            ->method('getEntityAttributeName')
            ->will($this->returnValue('attr'));

        /** @var WorkflowItem $workflowItem */
        $workflowItem = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowItem')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();
        $workflowItem->setDefinition($definition);

        $serializedData = 'serialized_data';

        $data = new WorkflowData();
        $data->set('foo', 'bar');

        $serializer = $this->getMock('Oro\Bundle\WorkflowBundle\Serializer\WorkflowAwareSerializer');
        $serializer->expects($this->once())->method('deserialize')
            ->with($serializedData, 'Oro\Bundle\WorkflowBundle\Model\WorkflowData', 'json')
            ->will($this->returnValue($data));

        $workflowItem->setSerializer($serializer, 'json');
        $workflowItem->setSerializedData($serializedData);

        $this->assertSame($data, $workflowItem->getData());
        $this->assertSame($data, $workflowItem->getData());
    }

    /**
     * @expectedException \Oro\Bundle\WorkflowBundle\Exception\WorkflowException
     * @expectedExceptionMessage Cannot deserialize data of workflow item. Serializer is not available.
     */
    public function testGetDataWithSerializationFails()
    {
        /** @var WorkflowItem $workflowItem */
        $workflowItem = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowItem')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();
        $workflowItem->setSerializedData('serialized_data');
        $workflowItem->getData();
    }

    public function testGetDataWithWithEmptySerializedData()
    {
        /** @var WorkflowItem $workflowItem */
        $workflowItem = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowItem')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $data = $workflowItem->getData();
        $this->assertInstanceOf('Oro\Bundle\WorkflowBundle\Model\WorkflowData', $data);
        $this->assertTrue($data->isEmpty());
    }

    public function testSetSerializedData()
    {
        $this->assertAttributeEmpty('serializedData', $this->workflowItem);
        $data = 'serialized_data';
        $this->workflowItem->setSerializedData($data);
        $this->assertAttributeEquals($data, 'serializedData', $this->workflowItem);
    }

    public function testGetSerializedData()
    {
        $this->assertNull($this->workflowItem->getSerializedData());
        $data = 'serialized_data';
        $this->workflowItem->setSerializedData($data);
        $this->assertEquals($data, $this->workflowItem->getSerializedData());
    }

    public function testGetResult()
    {
        $this->assertInstanceOf('Oro\Bundle\WorkflowBundle\Model\WorkflowResult', $this->workflowItem->getResult());
        $this->assertTrue($this->workflowItem->getResult()->isEmpty());
    }

    /**
     * @depends testGetResult
     */
    public function testGetResultUnserialized()
    {
        $reflection = new \ReflectionObject($this->workflowItem);
        $resultProperty = $reflection->getProperty('result');
        $resultProperty->setAccessible(true);
        $resultProperty->setValue($this->workflowItem, null);
        $this->assertInstanceOf('Oro\Bundle\WorkflowBundle\Model\WorkflowResult', $this->workflowItem->getResult());
        $this->assertTrue($this->workflowItem->getResult()->isEmpty());
    }

    public function testDefinition()
    {
        $this->assertNull($this->workflowItem->getDefinition());
        $value = new WorkflowDefinition();
        $this->workflowItem->setDefinition($value);
        $this->assertEquals($value, $this->workflowItem->getDefinition());
    }

    public function testCreatedAtAndPrePersist()
    {
        $this->assertNull($this->workflowItem->getCreated());
        $this->workflowItem->prePersist();
        $this->assertInstanceOf('DateTime', $this->workflowItem->getCreated());

        $this->assertEquals(time(), $this->workflowItem->getCreated()->getTimestamp(), '', 5);
    }

    public function testUpdatedAndPreUpdate()
    {
        $this->assertNull($this->workflowItem->getUpdated());
        $this->workflowItem->preUpdate();
        $this->assertInstanceOf('DateTime', $this->workflowItem->getUpdated());

        $this->assertEquals(time(), $this->workflowItem->getUpdated()->getTimestamp(), '', 5);
    }

    public function testGetAddTransitionRecords()
    {
        $this->assertEmpty($this->workflowItem->getTransitionRecords()->getValues());

        $transitionRecord = new WorkflowTransitionRecord();
        $transitionRecord->setTransitionName('test_transition');

        $this->assertEquals($this->workflowItem, $this->workflowItem->addTransitionRecord($transitionRecord));
        $this->assertEquals(array($transitionRecord), $this->workflowItem->getTransitionRecords()->getValues());
        $this->assertEquals($this->workflowItem, $transitionRecord->getWorkflowItem());
    }

    public function testEntity()
    {
        $entity = new \stdClass();
        $this->assertSame($this->workflowItem, $this->workflowItem->setEntity($entity));
        $this->assertEquals($entity, $this->workflowItem->getEntity());
    }

    /**
     * @expectedException \Oro\Bundle\WorkflowBundle\Exception\WorkflowException
     * @expectedExceptionMessage Workflow item entity can not be changed
     */
    public function testSetEntityException()
    {
        $this->workflowItem->setEntity(new \stdClass());
        $this->workflowItem->setEntity(new \stdClass());
    }

    public function testEntityId()
    {
        $entityId = 1;
        $this->assertSame($this->workflowItem, $this->workflowItem->setEntityId($entityId));
        $this->assertEquals($entityId, $this->workflowItem->getEntityId());
    }

    /**
     * @expectedException \Oro\Bundle\WorkflowBundle\Exception\WorkflowException
     * @expectedExceptionMessage Workflow item entity ID can not be changed
     */
    public function testSetEntityIdException()
    {
        $this->workflowItem->setEntityId(1);
        $this->workflowItem->setEntityId(2);
    }

    public function testSetGetAclIdentities()
    {
        $firstStep = new WorkflowStep();
        $firstStep->setName('first_step');
        $secondStep = new WorkflowStep();
        $secondStep->setName('second_step');

        $firstEntityAcl = new WorkflowEntityAcl();
        $firstEntityAcl->setStep($firstStep)->setAttribute('first_attribute');
        $secondEntityAcl = new WorkflowEntityAcl();
        $secondEntityAcl->setStep($secondStep)->setAttribute('second_attribute');

        $firstAclIdentity = new WorkflowEntityAclIdentity();
        $firstAclIdentity->setAcl($firstEntityAcl);
        $alternativeFirstAclIdentity = new WorkflowEntityAclIdentity();
        $alternativeFirstAclIdentity->setAcl($firstEntityAcl);
        $secondAclIdentity = new WorkflowEntityAclIdentity();
        $secondAclIdentity->setAcl($secondEntityAcl);

        // default
        $this->assertEmpty($this->workflowItem->getAclIdentities()->toArray());

        // adding
        $this->workflowItem->setAclIdentities(array($firstAclIdentity));
        $this->assertCount(1, $this->workflowItem->getAclIdentities());
        $this->assertEquals($firstAclIdentity, $this->workflowItem->getAclIdentities()->first());

        // merging
        $this->workflowItem->setAclIdentities(array($alternativeFirstAclIdentity, $secondAclIdentity));
        $this->assertCount(2, $this->workflowItem->getAclIdentities());
        $aclIdentities = array_values($this->workflowItem->getAclIdentities()->toArray());
        $this->assertEquals($firstAclIdentity, $aclIdentities[0]);
        $this->assertEquals($secondAclIdentity, $aclIdentities[1]);

        // removing
        $this->workflowItem->setAclIdentities(array($secondAclIdentity));
        $this->assertCount(1, $this->workflowItem->getAclIdentities());
        $this->assertEquals($secondAclIdentity, $this->workflowItem->getAclIdentities()->first());

        // resetting
        $this->workflowItem->setAclIdentities(array());
        $this->assertEmpty($this->workflowItem->getAclIdentities()->toArray());
    }

    public function testSetGetRestrictionIdentities()
    {
        $firstStep = new WorkflowStep();
        $firstStep->setName('first_step');
        $secondStep = new WorkflowStep();
        $secondStep->setName('second_step');

        $firstRestriction = new WorkflowRestriction();
        $firstRestriction->setStep($firstStep)->setAttribute('first_attribute');
        $secondRestriction = new WorkflowRestriction();
        $secondRestriction->setStep($secondStep)->setAttribute('second_attribute');

        $firstIdentity = new WorkflowRestrictionIdentity();
        $firstIdentity->setRestriction($firstRestriction);
        $alternativeFirstIdentity = new WorkflowRestrictionIdentity();
        $alternativeFirstIdentity->setRestriction($firstRestriction);
        $secondIdentity = new WorkflowRestrictionIdentity();
        $secondIdentity->setRestriction($secondRestriction);

        // default
        $this->assertEmpty($this->workflowItem->getRestrictionIdentities()->toArray());

        // adding
        $this->workflowItem->setRestrictionIdentities([$firstIdentity]);
        $this->assertCount(1, $this->workflowItem->getRestrictionIdentities());
        $this->assertEquals($firstIdentity, $this->workflowItem->getRestrictionIdentities()->first());

        // merging
        $this->workflowItem->setRestrictionIdentities([$alternativeFirstIdentity, $secondIdentity]);
        $this->assertCount(2, $this->workflowItem->getRestrictionIdentities());
        $identities = array_values($this->workflowItem->getRestrictionIdentities()->toArray());
        $this->assertEquals($firstIdentity, $identities[0]);
        $this->assertEquals($secondIdentity, $identities[1]);

        // removing
        $this->workflowItem->setRestrictionIdentities([$secondIdentity]);
        $this->assertCount(1, $this->workflowItem->getRestrictionIdentities());
        $this->assertEquals($secondIdentity, $this->workflowItem->getRestrictionIdentities()->first());

        // resetting
        $this->workflowItem->setRestrictionIdentities([]);
        $this->assertEmpty($this->workflowItem->getRestrictionIdentities()->toArray());
    }

    public function testEntityClass()
    {
        $entityClass = new \stdClass();
        $this->assertSame($this->workflowItem, $this->workflowItem->setEntityClass($entityClass));
        $this->assertEquals(get_class($entityClass), $this->workflowItem->getEntityClass());
    }

    /**
     * @expectedException \Oro\Bundle\WorkflowBundle\Exception\WorkflowException
     * @expectedExceptionMessage Workflow item entity CLASS can not be changed
     */
    public function testSetEntityClassException()
    {
        $this->workflowItem->setEntityClass('stdClass');
        $this->workflowItem->setEntityClass('test');
    }
}
