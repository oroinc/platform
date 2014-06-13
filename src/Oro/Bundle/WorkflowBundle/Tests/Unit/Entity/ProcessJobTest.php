<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Entity;

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

    public function testGetDataWithSerialization()
    {
        $originalData = new ProcessData(array('original_data'));
        $serializedData = 'serialized_data';

        $serializer = $this->getMockForAbstractClass('Symfony\Component\Serializer\SerializerInterface');
        $serializer->expects($this->once())
            ->method('deserialize')
            ->with($serializedData, 'Oro\Bundle\WorkflowBundle\Model\ProcessData', 'json')
            ->will($this->returnValue($originalData));

        $this->entity->setSerializer($serializer, 'json');
        $this->entity->setSerializedData($serializedData);

        $this->assertSame($originalData, $this->entity->getData());
        $this->assertSame($originalData, $this->entity->getData());
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
            'entityHash' => array('entityHash', 'My\Entity' . serialize(array('id' => 1))),
            'serializedData' => array('serializedData', serialize(array('some' => 'data'))),
            'data' => array('data', new ProcessData(array('some' => 'data')), new ProcessData(array())),
        );
    }
}
