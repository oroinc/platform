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
use Oro\Bundle\WorkflowBundle\Exception\WorkflowException;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Bundle\WorkflowBundle\Model\WorkflowResult;
use Oro\Bundle\WorkflowBundle\Serializer\WorkflowAwareSerializer;
use Oro\Component\Testing\ReflectionUtil;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class WorkflowItemTest extends \PHPUnit\Framework\TestCase
{
    /** @var WorkflowItem */
    private $workflowItem;

    protected function setUp(): void
    {
        $this->workflowItem = new WorkflowItem();
    }

    public function testId()
    {
        self::assertNull($this->workflowItem->getId());
        $value = 1;
        $this->workflowItem->setId($value);
        self::assertEquals($value, $this->workflowItem->getId());
    }

    public function testMerge()
    {
        $object = (object)['prop1' => 'val1'];

        $source = new WorkflowItem();
        $source->getData()->add(['key11' => 'val11', 'obj' => $object]);
        $source->getResult()->add(['key12' => 'val12', 'obj' => $object]);

        $dest = new WorkflowItem();
        $dest->getData()->add(['key21' => 'val21']);
        $dest->getResult()->add(['key22' => 'val22']);

        $exp = new WorkflowItem();
        $exp->getData()->add(['key11' => 'val11', 'key21' => 'val21', 'obj' => $object]);
        $exp->getResult()->add(['key12' => 'val12', 'key22' => 'val22', 'obj' => $object]);

        $this->assertEquals($exp, $dest->merge($source));
        $this->assertSame($object, $dest->getData()->get('obj'));
        $this->assertSame($object, $dest->getResult()->get('obj'));
    }

    public function testWorkflowName()
    {
        self::assertNull($this->workflowItem->getWorkflowName());
        $value = 'example_workflow';
        $this->workflowItem->setWorkflowName($value);
        self::assertEquals($value, $this->workflowItem->getWorkflowName());
    }

    public function testCurrentStepName()
    {
        self::assertNull($this->workflowItem->getCurrentStep());
        $value = $this->createMock(WorkflowStep::class);
        $this->workflowItem->setCurrentStep($value);
        self::assertEquals($value, $this->workflowItem->getCurrentStep());
    }

    public function testData()
    {
        $this->assertInstanceOf(WorkflowData::class, $this->workflowItem->getData());

        $data = new WorkflowData();
        $data['foo'] = 'Bar';

        $this->workflowItem->setData($data);
        $this->assertEquals($data, $this->workflowItem->getData());
    }

    public function testGetDataWithSerialization()
    {
        $definition = $this->createMock(WorkflowDefinition::class);
        $definition->expects(self::once())
            ->method('getEntityAttributeName')
            ->willReturn('attr');

        $workflowItem = $this->getMockBuilder(WorkflowItem::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $workflowItem->setDefinition($definition);

        $serializedData = 'serialized_data';

        $data = new WorkflowData();
        $data->set('foo', 'bar');

        $serializer = $this->createMock(WorkflowAwareSerializer::class);
        $serializer->expects($this->once())
            ->method('deserialize')
            ->with($serializedData, WorkflowData::class, 'json')
            ->willReturn($data);

        $workflowItem->setSerializer($serializer, 'json');
        $workflowItem->setSerializedData($serializedData);

        $this->assertSame($data, $workflowItem->getData());
        $this->assertSame($data, $workflowItem->getData());
    }

    public function testGetDataWithSerializationFails()
    {
        $this->expectException(WorkflowException::class);
        $this->expectExceptionMessage('Cannot deserialize data of workflow item. Serializer is not available.');

        $workflowItem = $this->getMockBuilder(WorkflowItem::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $workflowItem->setSerializedData('serialized_data');
        $workflowItem->getData();
    }

    public function testGetDataWithWithEmptySerializedData()
    {
        $workflowItem = $this->getMockBuilder(WorkflowItem::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $data = $workflowItem->getData();
        self::assertInstanceOf(WorkflowData::class, $data);
        self::assertTrue($data->isEmpty());
    }

    public function testSetSerializedData()
    {
        self::assertEmpty($this->workflowItem->getSerializedData());
        $data = 'serialized_data';
        $this->workflowItem->setSerializedData($data);
        self::assertEquals($data, $this->workflowItem->getSerializedData());
    }

    public function testGetSerializedData()
    {
        self::assertNull($this->workflowItem->getSerializedData());
        $data = 'serialized_data';
        $this->workflowItem->setSerializedData($data);
        self::assertEquals($data, $this->workflowItem->getSerializedData());
    }

    public function testGetResult()
    {
        self::assertInstanceOf(WorkflowResult::class, $this->workflowItem->getResult());
        self::assertTrue($this->workflowItem->getResult()->isEmpty());
    }

    /**
     * @depends testGetResult
     */
    public function testGetResultUnserialized()
    {
        ReflectionUtil::setPropertyValue($this->workflowItem, 'result', null);
        $this->assertInstanceOf(WorkflowResult::class, $this->workflowItem->getResult());
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

        $this->assertEqualsWithDelta(time(), $this->workflowItem->getCreated()->getTimestamp(), 5);
    }

    public function testUpdatedAndPreUpdate()
    {
        $this->assertNull($this->workflowItem->getUpdated());
        $this->workflowItem->preUpdate();
        $this->assertInstanceOf('DateTime', $this->workflowItem->getUpdated());

        $this->assertEqualsWithDelta(time(), $this->workflowItem->getUpdated()->getTimestamp(), 5);
    }

    public function testGetAddTransitionRecords()
    {
        $this->assertEmpty($this->workflowItem->getTransitionRecords()->getValues());

        $transitionRecord = new WorkflowTransitionRecord();
        $transitionRecord->setTransitionName('test_transition');

        $this->assertEquals($this->workflowItem, $this->workflowItem->addTransitionRecord($transitionRecord));
        $this->assertEquals([$transitionRecord], $this->workflowItem->getTransitionRecords()->getValues());
        $this->assertEquals($this->workflowItem, $transitionRecord->getWorkflowItem());
    }

    public function testEntity()
    {
        $entity = new \stdClass();
        $this->assertSame($this->workflowItem, $this->workflowItem->setEntity($entity));
        $this->assertEquals($entity, $this->workflowItem->getEntity());
    }

    public function testSetEntityException()
    {
        $this->expectException(WorkflowException::class);
        $this->expectExceptionMessage('Workflow item entity can not be changed');

        $this->workflowItem->setEntity(new \stdClass());
        $this->workflowItem->setEntity(new \stdClass());
    }

    public function testEntityId()
    {
        $entityId = 1;
        $this->assertSame($this->workflowItem, $this->workflowItem->setEntityId($entityId));
        $this->assertEquals($entityId, $this->workflowItem->getEntityId());
    }

    public function testSetEntityIdException()
    {
        $this->expectException(WorkflowException::class);
        $this->expectExceptionMessage('Workflow item entity ID can not be changed');

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
        $this->workflowItem->setAclIdentities([$firstAclIdentity]);
        $this->assertCount(1, $this->workflowItem->getAclIdentities());
        $this->assertEquals($firstAclIdentity, $this->workflowItem->getAclIdentities()->first());

        // merging
        $this->workflowItem->setAclIdentities([$alternativeFirstAclIdentity, $secondAclIdentity]);
        $this->assertCount(2, $this->workflowItem->getAclIdentities());
        $aclIdentities = array_values($this->workflowItem->getAclIdentities()->toArray());
        $this->assertEquals($firstAclIdentity, $aclIdentities[0]);
        $this->assertEquals($secondAclIdentity, $aclIdentities[1]);

        // removing
        $this->workflowItem->setAclIdentities([$secondAclIdentity]);
        $this->assertCount(1, $this->workflowItem->getAclIdentities());
        $this->assertEquals($secondAclIdentity, $this->workflowItem->getAclIdentities()->first());

        // resetting
        $this->workflowItem->setAclIdentities([]);
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

    public function testSetEntityClassException()
    {
        $this->expectException(WorkflowException::class);
        $this->expectExceptionMessage('Workflow item entity CLASS can not be changed');

        $this->workflowItem->setEntityClass('stdClass');
        $this->workflowItem->setEntityClass('test');
    }

    public function testSetRedirectUrl()
    {
        $this->workflowItem->setRedirectUrl('test_url');

        $this->assertEquals('test_url', $this->workflowItem->getResult()->get('redirectUrl'));
    }

    public function testToString()
    {
        $step = new WorkflowStep();
        $step->setName('test_step');

        $this->workflowItem->setWorkflowName('test_workflow')
            ->setEntityClass('stdClass')
            ->setEntityId('42')
            ->setCurrentStep($step);

        $this->assertEquals('[test_workflow] stdClass:42 test_step', (string)$this->workflowItem);
    }
}
