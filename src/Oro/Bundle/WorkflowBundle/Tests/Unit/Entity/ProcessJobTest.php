<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Entity;

use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\ProcessJob;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\Exception\SerializerException;
use Oro\Bundle\WorkflowBundle\Model\ProcessData;
use Symfony\Component\Serializer\SerializerInterface;

class ProcessJobTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProcessJob */
    protected $entity;

    protected function setUp(): void
    {
        $this->entity = new ProcessJob();
    }

    protected function tearDown(): void
    {
        unset($this->entity);
    }

    public function testGetId()
    {
        static::assertNull($this->entity->getId());

        $testValue = 1;
        $reflectionProperty = new \ReflectionProperty(ProcessJob::class, 'id');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this->entity, $testValue);

        static::assertEquals($testValue, $this->entity->getId());
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

        static::assertEquals($defaultValue, $this->entity->$getter());
        static::assertSame($this->entity, $this->entity->$setter($testValue));
        static::assertSame($testValue, $this->entity->$getter());
    }

    /**
     * @return array
     */
    public function setGetDataProvider()
    {
        return [
            'processTrigger' => ['processTrigger', new ProcessTrigger()],
            'serializedData' => ['serializedData', serialize(['some' => 'data'])],
            'data' => ['data', new ProcessData(['some' => 'data']), new ProcessData()],
        ];
    }

    public function testGetDataWithSerializationFails()
    {
        $this->expectException(SerializerException::class);
        $this->expectExceptionMessage('Cannot deserialize data of process job. Serializer is not available.');

        $this->entity->setSerializedData('serialized_data');
        $this->entity->getData();
    }

    /**
     * @dataProvider getDataWithSerializationProvider
     */
    public function testGetDataWithSerialization($data)
    {
        $isDataNull = is_null($data);
        $serializer = $this->getMockForAbstractClass(SerializerInterface::class);

        if (!$isDataNull && empty($data)) {
            $originalData = new ProcessData($data);
            $serializedData = $data;
            $serializer->expects(static::never())
                ->method('deserialize');
        } else {
            $originalData = $isDataNull ? null : new ProcessData($data);
            $serializedData = 'serialized_data';
            $serializer->expects(static::exactly($isDataNull ? 2 : 1))
                ->method('deserialize')
                ->with($serializedData, ProcessData::class, 'json')
                ->willReturn($originalData);
        }

        $this->entity->setSerializer($serializer, 'json');
        $this->entity->setSerializedData($serializedData);

        static::assertEquals($originalData, $this->entity->getData());
        static::assertEquals($originalData, $this->entity->getData());
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
        static::assertInstanceOf(ProcessData::class, $data);
        static::assertTrue($data->isEmpty());
    }

    public function testSetSerializedData()
    {
        static::assertEmpty($this->entity->getSerializedData());
        $data = 'serialized_data';
        $this->entity->setSerializedData($data);
        static::assertEquals($data, $this->entity->getSerializedData());
    }

    public function testGetSerializedData()
    {
        static::assertNull($this->entity->getSerializedData());
        $data = 'serialized_data';
        $this->entity->setSerializedData($data);
        static::assertEquals($data, $this->entity->getSerializedData());
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

        static::assertNull($this->entity->getEntityId());
        static::assertNull($this->entity->getEntityHash());

        $this->entity->setEntityId($entityId);

        static::assertEquals($entityId, $this->entity->getEntityId());
        static::assertEquals(ProcessJob::generateEntityHash($entityClass, $entityId), $this->entity->getEntityHash());

        $this->entity->setEntityId(null);

        static::assertNull($this->entity->getEntityId());
        static::assertNull($this->entity->getEntityHash());
    }

    public function testSetEntityIdNoTrigger()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Process trigger must be defined for process jo');

        $this->entity->setEntityId(1);
    }

    public function testSetEntityIdNoDefinition()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Process definition must be defined for process jo');

        $this->entity->setProcessTrigger(new ProcessTrigger());
        $this->entity->setEntityId(1);
    }
}
