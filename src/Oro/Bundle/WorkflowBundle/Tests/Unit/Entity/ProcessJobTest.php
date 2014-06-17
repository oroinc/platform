<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Entity;

use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\Entity\ProcessJob;
use Oro\Bundle\WorkflowBundle\Model\ProcessData;

class ProcessJobTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProcessJob
     */
    protected $entity;

    protected function setUp()
    {
        $this->entity = new ProcessJob();
    }

    protected function tearDown()
    {
        unset($this->entity);
    }

    public function testGetId()
    {
        $this->assertNull($this->entity->getId());

        $testValue = 1;
        $reflectionProperty = new \ReflectionProperty('\Oro\Bundle\WorkflowBundle\Entity\ProcessJob', 'id');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this->entity, $testValue);

        $this->assertEquals($testValue, $this->entity->getId());
    }

    /**
     * @param mixed $propertyName
     * @param mixed $testValue
     * @param mixed $defaultValue
     * @dataProvider setGetDataProvider
     */
    public function testSetGetEntity($propertyName, $testValue, $defaultValue = null)
    {
        $setter = 'set' . ucfirst($propertyName);
        $getter = (is_bool($testValue) ? 'is' : 'get') . ucfirst($propertyName);

        $this->assertEquals($defaultValue, $this->entity->$getter());
        $this->assertSame($this->entity, $this->entity->$setter($testValue));
        $this->assertSame($testValue, $this->entity->$getter());
    }

    /**
     * @expectedException \Oro\Bundle\WorkflowBundle\Exception\SerializerException
     * @expectedExceptionMessage Cannot deserialize data of process job. Serializer is not available.
     */
    public function testGetDataWithSerializationFails()
    {
        $this->entity->setSerializedData('serialized_data');
        $this->entity->getData();
    }

    /**
     * @dataProvider getDataWithSerializationProvider
     */
    public function testGetDataWithSerialization($data)
    {
        $isDataNull = is_null($data);
        $serializer = $this->getMockForAbstractClass('Symfony\Component\Serializer\SerializerInterface');

        if (!$isDataNull && empty($data)) {
            $originalData = new ProcessData($data);
            $serializedData = $data;
            $serializer->expects($this->never())
                ->method('deserialize');
        } else {
            $originalData = $isDataNull ? null : new ProcessData($data);
            $serializedData = 'serialized_data';
            $serializer->expects($this->exactly($isDataNull ? 2 : 1))
                ->method('deserialize')
                ->with($serializedData, 'Oro\Bundle\WorkflowBundle\Model\ProcessData', 'json')
                ->will($this->returnValue($originalData));
        }

        $this->entity->setSerializer($serializer, 'json');
        $this->entity->setSerializedData($serializedData);

        $this->assertEquals($originalData, $this->entity->getData());
        $this->assertEquals($originalData, $this->entity->getData());
    }

    public function getDataWithSerializationProvider()
    {
        return array(
            'when data is null' => array(
                'data' => null
            ),
            'when data is empty' => array(
                'data' => array()
            ),
            'when data is filled' => array(
                'data' => array('some_data' => 'some_value')
            )
        );
    }

    public function testGetDataWithEmptySerializedData()
    {
        $data = $this->entity->getData();
        $this->assertInstanceOf('Oro\Bundle\WorkflowBundle\Model\ProcessData', $data);
        $this->assertTrue($data->isEmpty());
    }

    public function testSetSerializedData()
    {
        $this->assertAttributeEmpty('serializedData', $this->entity);
        $data = 'serialized_data';
        $this->entity->setSerializedData($data);
        $this->assertAttributeEquals($data, 'serializedData', $this->entity);
    }

    public function testGetSerializedData()
    {
        $this->assertNull($this->entity->getSerializedData());
        $data = 'serialized_data';
        $this->entity->setSerializedData($data);
        $this->assertEquals($data, $this->entity->getSerializedData());
    }

    /**
     * @return array
     */
    public function setGetDataProvider()
    {
        return array(
            'processTrigger' => array('processTrigger', new ProcessTrigger()),
            'serializedData' => array('serializedData', serialize(array('some' => 'data'))),
            'data' => array('data', new ProcessData(array('some' => 'data')), new ProcessData()),
        );
    }

    public function testSetGetEntityIdAndHash()
    {
        $entityClass = 'Test\Entity';
        $entityId = 12;

        $definition = new ProcessDefinition();
        $definition->setRelatedEntity($entityClass);

        $trigger = new ProcessTrigger();
        $trigger->setDefinition($definition);

        $this->entity->setProcessTrigger($trigger);

        $this->assertNull($this->entity->getEntityId());
        $this->assertNull($this->entity->getEntityHash());

        $this->entity->setEntityId($entityId);

        $this->assertEquals($entityId, $this->entity->getEntityId());
        $this->assertEquals(ProcessJob::generateEntityHash($entityClass, $entityId), $this->entity->getEntityHash());

        $this->entity->setEntityId(null);

        $this->assertNull($this->entity->getEntityId());
        $this->assertNull($this->entity->getEntityHash());
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Process trigger must be defined for process jo
     */
    public function testSetEntityIdNoTrigger()
    {
        $this->entity->setEntityId(1);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Process definition must be defined for process jo
     */
    public function testSetEntityIdNoDefinition()
    {
        $this->entity->setProcessTrigger(new ProcessTrigger());
        $this->entity->setEntityId(1);
    }
}
