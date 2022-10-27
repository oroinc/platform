<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Unit\Model\Accessor;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ActivityListBundle\Entity\Repository\ActivityListRepository;
use Oro\Bundle\ActivityListBundle\Model\Accessor\ActivityAccessor;
use Oro\Bundle\ActivityListBundle\Tests\Unit\Stub\EntityStub;
use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;

class ActivityAccessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var ActivityAccessor */
    private $accessor;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->accessor = new ActivityAccessor($this->doctrine);
    }

    public function testGetName()
    {
        $this->assertEquals('activity', $this->accessor->getName());
    }

    /**
     * @dataProvider getValueDataProvider
     */
    public function testGetValue(object $entity, FieldMetadata $metadata, int $count, $expectedValue)
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $repository = $this->createMock(ActivityListRepository::class);

        $repository->expects($this->once())
            ->method('getRecordsCountForTargetClassAndId')
            ->with(ClassUtils::getClass($entity), $entity->getId(), [$metadata->get('type')])
            ->willReturn($count);
        $em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repository);
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($em);

        $this->assertEquals($expectedValue, $this->accessor->getValue($entity, $metadata));
    }

    public function getValueDataProvider(): array
    {
        return [
            'activity' => [
                'entity' => new EntityStub('foo'),
                'metadata' => $this->getFieldMetadata('id', ['activity' => true, 'type' => 'test']),
                'count' => 123,
                'expected' => '123',
            ],
        ];
    }

    private function getFieldMetadata(string $fieldName = null, array $options = []): FieldMetadata
    {
        $result = $this->createMock(FieldMetadata::class);
        $result->expects($this->any())
            ->method('getFieldName')
            ->willReturn($fieldName);
        $result->expects($this->any())
            ->method('get')
            ->willReturnCallback(function ($code) use ($options) {
                $this->assertArrayHasKey($code, $options);

                return $options[$code];
            });
        $result->expects($this->any())
            ->method('has')
            ->willReturnCallback(function ($code) use ($options) {
                return isset($options[$code]);
            });

        return $result;
    }
}
