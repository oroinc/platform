<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Entity;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserApi;
use Oro\Component\Testing\ReflectionUtil;

class UserApiTest extends \PHPUnit\Framework\TestCase
{
    public function testId()
    {
        $entity = new UserApi();
        self::assertNull($entity->getId());

        $id = 1;
        ReflectionUtil::setId($entity, $id);
        self::assertSame($id, $entity->getId());
    }

    public function testApiKey()
    {
        $entity = new UserApi();
        self::assertNull($entity->getApiKey());

        $apiKey = 'test';
        $entity->setApiKey($apiKey);
        self::assertEquals($apiKey, $entity->getApiKey());
    }

    public function testUser()
    {
        $entity = new UserApi();
        self::assertNull($entity->getUser());

        $user = new User();
        $entity->setUser($user);
        self::assertSame($user, $entity->getUser());
    }

    public function testOrganization()
    {
        $entity = new UserApi();
        self::assertNull($entity->getOrganization());

        $organization = new Organization();
        $entity->setOrganization($organization);
        self::assertSame($organization, $entity->getOrganization());
    }

    public function testGenerateKey()
    {
        $entity = new UserApi();
        self::assertNotEmpty($entity->generateKey());
    }

    public function testIsEnabled()
    {
        $entity = new UserApi();

        $organization1 = new Organization();
        $organization2 = new Organization();
        $user = new User();
        $user->addOrganization($organization2);
        $entity->setUser($user);
        $entity->setOrganization($organization1);

        self::assertFalse($entity->isEnabled());

        $user->addOrganization($organization1);
        self::assertTrue($entity->isEnabled());
    }
}
