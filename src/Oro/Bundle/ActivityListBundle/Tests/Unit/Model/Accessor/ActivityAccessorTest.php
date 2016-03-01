<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Unit\Model\Accessor;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\ActivityListBundle\Model\Accessor\ActivityAccessor;
use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;
use Oro\Bundle\ActivityListBundle\Tests\Unit\Stub\EntityStub;

class ActivityAccessorTest extends \PHPUnit_Framework_TestCase
{
    /** @var ActivityAccessor $fieldAccessor */
    protected $accessor;

    /** @var Registry|\PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    protected function setUp()
    {
        $this->registry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->accessor = new ActivityAccessor($this->registry); //, $this->translator);
    }

    public function testGetName()
    {
        $this->assertEquals('activity', $this->accessor->getName());
    }

    /**
     * @param $entity
     * @param FieldMetadata $metadata
     * @param int $count
     * @param $expectedValue
     *
     * @dataProvider getValueDataProvider
     */
    public function testGetValue($entity, FieldMetadata $metadata, $count, $expectedValue)
    {
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()->getMock();
        $repository = $this->getMockBuilder('Oro\Bundle\ActivityListBundle\Entity\Repository\ActivityListRepository')
            ->disableOriginalConstructor()->getMock();

        $repository->expects($this->once())
            ->method('getRecordsCountForTargetClassAndId')
            ->with(ClassUtils::getClass($entity), $entity->getId(), [$metadata->get('type')])
            ->willReturn($count);
        $em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repository);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($em);

        $this->assertEquals($expectedValue, $this->accessor->getValue($entity, $metadata));
    }

    public function getValueDataProvider()
    {
        return [
            'activity' => [
                'entity' => $this->createEntity('foo'),
                'metadata' => $this->getFieldMetadata('id', ['activity' => true, 'type' => 'test']),
                'count' => 123,
                'expected' => '123',
            ],
        ];
    }

    protected function createEntity($id = null)
    {
        return new EntityStub($id);
    }

    protected function getFieldMetadata($fieldName = null, array $options = [])
    {
        $result = $this->getMockBuilder('Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $result->expects($this->any())
            ->method('getFieldName')
            ->will($this->returnValue($fieldName));

        $result->expects($this->any())
            ->method('get')
            ->will(
                $this->returnCallback(
                    function ($code) use ($options) {
                        $this->assertArrayHasKey($code, $options);
                        return $options[$code];
                    }
                )
            );

        $result->expects($this->any())
            ->method('has')
            ->will(
                $this->returnCallback(
                    function ($code) use ($options) {
                        return isset($options[$code]);
                    }
                )
            );

        return $result;
    }
}
