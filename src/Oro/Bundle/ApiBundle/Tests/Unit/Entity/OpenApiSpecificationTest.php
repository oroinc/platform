<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Entity;

use Oro\Bundle\ApiBundle\Entity\OpenApiSpecification;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class OpenApiSpecificationTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testProperties(): void
    {
        $properties = [
            ['id', 1, null],
            ['owner', new User(), null],
            ['owner', null],
            ['organization', new Organization(), null],
            ['organization', null],
            ['name', 'test name', null],
            ['name', null],
            ['publicSlug', 'test-slug', null],
            ['publicSlug', null],
            ['view', 'test_view', null],
            ['view', null],
            ['format', 'test_format', null],
            ['format', null],
            ['entities', ['test_entity'], null],
            ['entities', null],
            ['specification', 'test specification', null],
            ['specification', null],
            ['specificationCreatedAt', new \DateTime('now', new \DateTimeZone('UTC')), null],
            ['specificationCreatedAt', null]
        ];

        self::assertPropertyAccessors(new OpenApiSpecification(), $properties);
    }

    public function testId(): void
    {
        $entity = new OpenApiSpecification();
        ReflectionUtil::setId($entity, 100);
        self::assertEquals(100, $entity->getId());
    }

    public function testCreatedAtForNewEntity(): void
    {
        $entity = new OpenApiSpecification();
        $entity->beforeSave();
        self::assertInstanceOf(\DateTime::class, $entity->getCreatedAt());
    }

    public function testStatusForNewEntity(): void
    {
        $entity = new OpenApiSpecification();
        $entity->beforeSave();
        self::assertEquals(OpenApiSpecification::STATUS_CREATING, $entity->getStatus());
    }

    public function testStatus(): void
    {
        $entity = new OpenApiSpecification();
        $entity->setStatus(OpenApiSpecification::STATUS_CREATED);
        self::assertEquals(OpenApiSpecification::STATUS_CREATED, $entity->getStatus());
    }

    public function testUpdatedAtForNewEntity(): void
    {
        $entity = new OpenApiSpecification();
        $entity->beforeSave();
        self::assertInstanceOf(\DateTime::class, $entity->getUpdatedAt());
        self::assertEquals($entity->getCreatedAt(), $entity->getUpdatedAt());
        self::assertNotSame($entity->getCreatedAt(), $entity->getUpdatedAt());
    }

    public function testUpdatedAtForUpdatedEntity(): void
    {
        $entity = new OpenApiSpecification();
        $entity->beforeSave();
        $previousUpdatedAt = $entity->getUpdatedAt();
        $entity->preUpdate();
        self::assertNotSame($previousUpdatedAt, $entity->getUpdatedAt());
    }
}
