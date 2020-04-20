<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Entity;

use Oro\Bundle\ApiBundle\Entity\AsyncOperation;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\PropertyAccess\PropertyAccess;

class AsyncOperationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider  getSetDataProvider
     *
     * @param string $property
     * @param mixed  $value
     */
    public function testSetGet($property, $value)
    {
        $entity = new AsyncOperation();
        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        $propertyAccessor->setValue($entity, $property, $value);
        self::assertEquals($value, $propertyAccessor->getValue($entity, $property));
    }

    public function getSetDataProvider()
    {
        return [
            'status'       => ['status', 'new'],
            'progress'     => ['progress', 12.1],
            'jobId'        => ['jobId', '45'],
            'owner'        => ['owner', new User()],
            'organization' => ['organization', new Organization()],
            'hasErrors'    => ['hasErrors', true],
            'dataFileName' => ['dataFileName', 'test_file'],
            'entityClass'  => ['entityClass', 'Acme\DemoBundle\Entity\Test'],
            'actionName'   => ['actionName', 'some_action']
        ];
    }

    public function testId()
    {
        $entity = new AsyncOperation();

        $reflectionClass = new \ReflectionClass(AsyncOperation::class);
        $property = $reflectionClass->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($entity, 100);

        self::assertEquals(100, $entity->getId());
    }

    public function testCreatedAtForNewEntity()
    {
        $entity = new AsyncOperation();
        $entity->beforeSave();
        self::assertInstanceOf(\DateTime::class, $entity->getCreatedAt());
    }

    public function testUpdatedAtForNewEntity()
    {
        $entity = new AsyncOperation();
        $entity->beforeSave();
        self::assertInstanceOf(\DateTime::class, $entity->getUpdatedAt());
        self::assertEquals($entity->getCreatedAt(), $entity->getUpdatedAt());
        self::assertNotSame($entity->getCreatedAt(), $entity->getUpdatedAt());
    }

    public function testUpdatedAtForUpdatedEntity()
    {
        $entity = new AsyncOperation();
        $entity->beforeSave();
        $previousUpdatedAt = $entity->getUpdatedAt();
        $entity->preUpdate();
        self::assertNotSame($previousUpdatedAt, $entity->getUpdatedAt());
    }

    public function testElapsedTimeForNewEntity()
    {
        $entity = new AsyncOperation();
        $entity->beforeSave();
        self::assertSame(0, $entity->getElapsedTime());
    }

    public function testElapsedTimeForUpdatedEntity()
    {
        $entity = new AsyncOperation();
        $entity->beforeSave();

        // now - 5 min 10 sec
        $createdAtProperty = new \ReflectionProperty(AsyncOperation::class, 'createdAt');
        $createdAtProperty->setAccessible(true);
        $createdAtProperty->setValue(
            $entity,
            (new \DateTime('now', new \DateTimeZone('UTC')))->sub(new \DateInterval('PT5M10S'))
        );
        $entity->preUpdate();

        self::assertSame(310, $entity->getElapsedTime());
    }
}
