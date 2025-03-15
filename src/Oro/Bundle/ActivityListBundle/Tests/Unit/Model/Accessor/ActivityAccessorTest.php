<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Unit\Model\Accessor;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ActivityListBundle\Entity\Repository\ActivityListRepository;
use Oro\Bundle\ActivityListBundle\Model\Accessor\ActivityAccessor;
use Oro\Bundle\ActivityListBundle\Tests\Unit\Stub\EntityStub;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ActivityAccessorTest extends TestCase
{
    private ManagerRegistry&MockObject $doctrine;
    private ActivityAccessor $accessor;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->accessor = new ActivityAccessor(
            PropertyAccess::createPropertyAccessor(),
            $this->doctrine
        );
    }

    private function getFieldMetadata(?string $fieldName = null, array $options = []): FieldMetadata
    {
        $result = $this->createMock(FieldMetadata::class);
        $result->expects(self::any())
            ->method('getFieldName')
            ->willReturn($fieldName);
        $result->expects(self::any())
            ->method('get')
            ->willReturnCallback(function ($code) use ($options) {
                self::assertArrayHasKey($code, $options);

                return $options[$code];
            });
        $result->expects(self::any())
            ->method('has')
            ->willReturnCallback(function ($code) use ($options) {
                return isset($options[$code]);
            });

        return $result;
    }

    public function testGetName(): void
    {
        self::assertEquals('activity', $this->accessor->getName());
    }

    public function testGetValue(): void
    {
        $entity = new EntityStub('foo');
        $metadata = $this->getFieldMetadata('id', ['activity' => true, 'type' => 'test']);
        $count = 123;
        $expectedValue = '123';

        $repository = $this->createMock(ActivityListRepository::class);
        $repository->expects(self::once())
            ->method('getRecordsCountForTargetClassAndId')
            ->with(ClassUtils::getClass($entity), $entity->getId(), [$metadata->get('type')])
            ->willReturn($count);
        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->willReturn($repository);

        self::assertEquals($expectedValue, $this->accessor->getValue($entity, $metadata));
    }
}
