<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Entity;

use Oro\Bundle\ApiBundle\Entity\AsyncOperation;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyAccess;

class AsyncOperationTest extends TestCase
{
    /**
     * @dataProvider getSetDataProvider
     */
    public function testSetGet(string $property, mixed $value, bool $allowNull = false): void
    {
        $entity = new AsyncOperation();
        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        if ($allowNull) {
            self::assertNull($propertyAccessor->getValue($entity, $property));
        }

        $propertyAccessor->setValue($entity, $property, $value);
        self::assertEquals($value, $propertyAccessor->getValue($entity, $property));
    }

    public function getSetDataProvider(): array
    {
        return [
            'status'           => ['status', 'new'],
            'progress'         => ['progress', 12.1, true],
            'jobId'            => ['jobId', '45', true],
            'owner'            => ['owner', new User(), true],
            'organization'     => ['organization', new Organization(), true],
            'hasErrors'        => ['hasErrors', true],
            'dataFileName'     => ['dataFileName', 'test_file', true],
            'entityClass'      => ['entityClass', 'Acme\DemoBundle\Entity\Test'],
            'actionName'       => ['actionName', 'some_action'],
            'summary'          => ['summary', ['key' => 'value'], true],
            'affectedEntities' => ['affectedEntities', [['type' => 'entity', 'id' => 1]], true]
        ];
    }

    public function testId(): void
    {
        $entity = new AsyncOperation();
        ReflectionUtil::setId($entity, 100);
        self::assertEquals(100, $entity->getId());
    }

    public function testCreatedAtForNewEntity(): void
    {
        $entity = new AsyncOperation();
        $entity->beforeSave();
        self::assertInstanceOf(\DateTime::class, $entity->getCreatedAt());
    }

    public function testUpdatedAtForNewEntity(): void
    {
        $entity = new AsyncOperation();
        $entity->beforeSave();
        self::assertInstanceOf(\DateTime::class, $entity->getUpdatedAt());
        self::assertEquals($entity->getCreatedAt(), $entity->getUpdatedAt());
        self::assertNotSame($entity->getCreatedAt(), $entity->getUpdatedAt());
    }

    public function testUpdatedAtForUpdatedEntity(): void
    {
        $entity = new AsyncOperation();
        $entity->beforeSave();
        $previousUpdatedAt = $entity->getUpdatedAt();
        $entity->preUpdate();
        self::assertNotSame($previousUpdatedAt, $entity->getUpdatedAt());
    }

    public function testElapsedTimeForNewEntity(): void
    {
        $entity = new AsyncOperation();
        $entity->beforeSave();
        self::assertSame(0, $entity->getElapsedTime());
    }

    public function testElapsedTimeForUpdatedEntity(): void
    {
        $entity = new AsyncOperation();
        $entity->beforeSave();

        // now - 5 min 10 sec
        ReflectionUtil::setPropertyValue(
            $entity,
            'createdAt',
            (new \DateTime('now', new \DateTimeZone('UTC')))->sub(new \DateInterval('PT5M10S'))
        );
        $entity->preUpdate();

        self::assertSame(310, $entity->getElapsedTime());
    }
}
