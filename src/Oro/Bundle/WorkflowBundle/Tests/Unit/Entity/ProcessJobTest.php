<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Entity;

use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\ProcessJob;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\Exception\SerializerException;
use Oro\Bundle\WorkflowBundle\Model\ProcessData;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Serializer\SerializerInterface;

class ProcessJobTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProcessJob */
    private $entity;

    protected function setUp(): void
    {
        $this->entity = new ProcessJob();
    }

    public function testGetId()
    {
        self::assertNull($this->entity->getId());

        $testValue = 1;
        ReflectionUtil::setId($this->entity, $testValue);
        self::assertSame($testValue, $this->entity->getId());
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

        self::assertEquals($defaultValue, $this->entity->$getter());
        self::assertSame($this->entity, $this->entity->$setter($testValue));
        self::assertSame($testValue, $this->entity->$getter());
    }

    public function setGetDataProvider(): array
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
            $serializer->expects(self::never())
                ->method('deserialize');
        } else {
            $originalData = $isDataNull ? null : new ProcessData($data);
            $serializedData = 'serialized_data';
            $serializer->expects(self::exactly($isDataNull ? 2 : 1))
                ->method('deserialize')
                ->with($serializedData, ProcessData::class, 'json')
                ->willReturn($originalData);
        }

        $this->entity->setSerializer($serializer, 'json');
        $this->entity->setSerializedData($serializedData);

        self::assertEquals($originalData, $this->entity->getData());
        self::assertEquals($originalData, $this->entity->getData());
    }

    public function getDataWithSerializationProvider(): array
    {
        return [
            'when data is null' => [
                'data' => null
            ],
            'when data is empty' => [
                'data' => []
            ],
            'when data is filled' => [
                'data' => ['some_data' => 'some_value']
            ]
        ];
    }

    public function testGetDataWithEmptySerializedData()
    {
        $data = $this->entity->getData();
        self::assertInstanceOf(ProcessData::class, $data);
        self::assertTrue($data->isEmpty());
    }

    public function testSetSerializedData()
    {
        self::assertEmpty($this->entity->getSerializedData());
        $data = 'serialized_data';
        $this->entity->setSerializedData($data);
        self::assertEquals($data, $this->entity->getSerializedData());
    }

    public function testGetSerializedData()
    {
        self::assertNull($this->entity->getSerializedData());
        $data = 'serialized_data';
        $this->entity->setSerializedData($data);
        self::assertEquals($data, $this->entity->getSerializedData());
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

        self::assertNull($this->entity->getEntityId());
        self::assertNull($this->entity->getEntityHash());

        $this->entity->setEntityId($entityId);

        self::assertEquals($entityId, $this->entity->getEntityId());
        self::assertEquals(ProcessJob::generateEntityHash($entityClass, $entityId), $this->entity->getEntityHash());

        $this->entity->setEntityId(null);

        self::assertNull($this->entity->getEntityId());
        self::assertNull($this->entity->getEntityHash());
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
