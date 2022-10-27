<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Entity;

use Oro\Bundle\NotificationBundle\Entity\NotificationAlert;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class NotificationAlertTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testNullableProperties(): void
    {
        $properties = [
            'operation'       => ['operation', 'import'],
            'step'            => ['step','save'],
            'itemId'          => ['itemId', 140],
            'externalId'      => ['externalId', 'testId'],
            'additionalInfo'  => ['additionalInfo', ['test' => 'value']]
        ];

        $entity = new NotificationAlert();
        self::assertPropertyAccessors($entity, $properties);
    }

    public function testGetId(): void
    {
        $id = UUIDGenerator::v4();
        $entity = new NotificationAlert();
        ReflectionUtil::setId($entity, $id);
        self::assertSame($id, $entity->getId());
    }

    public function testGetAndSetAlertType(): void
    {
        $alertType = 'auth';
        $entity = new NotificationAlert();
        $entity->setAlertType($alertType);
        self::assertSame($alertType, $entity->getAlertType());
    }

    public function testGetAndSetResourceType(): void
    {
        $resourceType = 'calendar';
        $entity = new NotificationAlert();
        $entity->setResourceType($resourceType);
        self::assertSame($resourceType, $entity->getResourceType());
    }

    public function testGetAndSetCreatedAt(): void
    {
        $createdAt = new \DateTime();
        $entity = new NotificationAlert();
        $entity->setCreatedAt($createdAt);
        self::assertSame($createdAt, $entity->getCreatedAt());
    }

    public function testGetAndSetUser(): void
    {
        $user = $this->createMock(User::class);
        $entity = new NotificationAlert();
        $entity->setUser($user);
        self::assertSame($user, $entity->getUser());
    }

    public function testGetAndSetOrganization(): void
    {
        $organization = $this->createMock(Organization::class);
        $entity = new NotificationAlert();
        $entity->setOrganization($organization);
        self::assertSame($organization, $entity->getOrganization());
    }

    public function testGetAndSetSourceType(): void
    {
        $sourceType = 'test_integration';
        $entity = new NotificationAlert();
        $entity->setSourceType($sourceType);
        self::assertSame($sourceType, $entity->getSourceType());
    }
}
