<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Model\Role as SecurityRole;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Tests\Unit\Stub\AbstractUserStub;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class AbstractUserTest extends \PHPUnit\Framework\TestCase
{
    public function getUser(): AbstractUser
    {
        return new AbstractUserStub();
    }

    public function testUsername(): void
    {
        $user = $this->getUser();
        $name = 'Tony';

        self::assertNull($user->getUsername());

        $user->setUsername($name);

        self::assertEquals($name, $user->getUsername());
        self::assertEquals($name, $user);
    }

    public function testIsPasswordRequestNonExpired(): void
    {
        $user = $this->getUser();
        $requested = new \DateTime('-10 seconds');

        $user->setPasswordRequestedAt($requested);

        self::assertSame($requested, $user->getPasswordRequestedAt());
        self::assertTrue($user->isPasswordRequestNonExpired(15));
        self::assertFalse($user->isPasswordRequestNonExpired(5));
    }

    public function testIsPasswordRequestAtCleared(): void
    {
        $user = $this->getUser();
        $requested = new \DateTime('-10 seconds');

        $user->setPasswordRequestedAt(null);
        self::assertTrue($user->isPasswordRequestNonExpired(15));

        $user->setPasswordRequestedAt($requested);
        self::assertFalse($user->isPasswordRequestNonExpired(5));
    }

    public function testConfirmationToken(): void
    {
        $user = $this->getUser();
        $token = $user->generateToken();

        self::assertNotEmpty($token);

        $user->setConfirmationToken($token);

        self::assertEquals($token, $user->getConfirmationToken());
    }

    public function testSetRolesWithArrayArgument(): void
    {
        $roles = [new Role(User::ROLE_DEFAULT)];
        $user = $this->getUser();
        self::assertEmpty($user->getUserRoles());
        $user->setUserRoles($roles);
        self::assertEquals($roles, $user->getUserRoles());
    }

    public function testSetRolesWithCollectionArgument(): void
    {
        $roles = new ArrayCollection([new Role(User::ROLE_DEFAULT)]);
        $user = $this->getUser();
        self::assertEmpty($user->getUserRoles());
        $user->setUserRoles($roles);
        self::assertEquals($roles->toArray(), $user->getUserRoles());
    }

    public function testSetRolesThrowsInvalidArgumentException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must be an instance of ' . SecurityRole::class . ' or an array');

        $user = $this->getUser();
        $user->setUserRoles('roles');
    }

    public function testHasRoleWithStringArgument(): void
    {
        $user = $this->getUser();
        $role = new Role(User::ROLE_DEFAULT);

        self::assertFalse($user->hasRole(User::ROLE_DEFAULT));
        $user->addUserRole($role);
        self::assertTrue($user->hasRole(User::ROLE_DEFAULT));
    }

    public function testHasRoleWithObjectArgument(): void
    {
        $user = $this->getUser();
        $role = new Role(User::ROLE_DEFAULT);

        self::assertFalse($user->hasRole($role));
        $user->addUserRole($role);
        self::assertTrue($user->hasRole($role));
    }

    public function testHasRoleThrowsInvalidArgumentException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must be an instance of ' . SecurityRole::class . ' or a string');

        $user = $this->getUser();
        $user->hasRole(new \stdClass());
    }

    public function testRemoveUserRoleWithStringArgument(): void
    {
        $user = $this->getUser();
        $role = new Role(User::ROLE_DEFAULT);
        $user->addUserRole($role);

        self::assertTrue($user->hasRole($role));
        $user->removeUserRole(User::ROLE_DEFAULT);
        self::assertFalse($user->hasRole($role));
    }

    public function testRemoveUserRoleWithObjectArgument(): void
    {
        $user = $this->getUser();
        $role = new Role(User::ROLE_DEFAULT);
        $user->addUserRole($role);

        self::assertTrue($user->hasRole($role));
        $user->removeUserRole($role);
        self::assertFalse($user->hasRole($role));
    }

    public function testRemoveUserRoleThrowsInvalidArgumentException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must be an instance of ' . SecurityRole::class . ' or a string');

        $user = $this->getUser();
        $user->removeUserRole(new \stdClass());
    }

    public function testIsEnabled(): void
    {
        $user = $this->getUser();

        self::assertTrue($user->isEnabled());

        $user->setEnabled(false);

        self::assertFalse($user->isEnabled());
    }

    public function testSerializing(): void
    {
        $user = $this->getUser();
        $clone = clone $user;
        $data = $user->__serialize();

        self::assertNotEmpty($data);

        $user
            ->setPassword('new-pass')
            ->setConfirmationToken('token')
            ->setUsername('new-name');

        $user->__unserialize($data);

        self::assertEquals($clone, $user);
    }

    public function testPassword(): void
    {
        $user = $this->getUser();
        $pass = 'anotherPassword';

        $user->setPassword($pass);
        $user->setPlainPassword($pass);

        self::assertEquals($pass, $user->getPassword());
        self::assertEquals($pass, $user->getPlainPassword());

        $user->eraseCredentials();

        self::assertNull($user->getPlainPassword());
    }

    public function testUnserialize(): void
    {
        $user = $this->getUser();
        $serialized = [
            'password',
            'salt',
            'username',
            true,
            'confirmation_token',
            10
        ];
        $user->__unserialize($serialized);

        self::assertEquals($serialized[0], $user->getPassword());
        self::assertEquals($serialized[1], $user->getSalt());
        self::assertEquals($serialized[2], $user->getUsername());
        self::assertEquals($serialized[3], $user->isEnabled());
        self::assertEquals($serialized[4], $user->getConfirmationToken());
        self::assertEquals($serialized[5], $user->getId());
    }

    /**
     * @dataProvider provider
     */
    public function testSettersAndGetters(string $property, mixed $value): void
    {
        $obj = $this->getUser();

        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $propertyAccessor->setValue($obj, $property, $value);
        self::assertEquals($value, $propertyAccessor->getValue($obj, $property));
    }

    public function provider(): array
    {
        return [
            ['username', 'test'],
            ['password', 'test'],
            ['plainPassword', 'test'],
            ['confirmationToken', 'test'],
            ['passwordRequestedAt', new \DateTime()],
            ['passwordChangedAt', new \DateTime()],
            ['lastLogin', new \DateTime()],
            ['loginCount', 11],
            ['salt', md5('user')],
        ];
    }

    public function testOrganization(): void
    {
        $entity = $this->getUser();
        $organization = new Organization();

        self::assertNull($entity->getOrganization());
        $entity->setOrganization($organization);
        self::assertSame($organization, $entity->getOrganization());
    }

    public function testGetRoles(): void
    {
        $user = $this->getUser();

        $user->addUserRole(new Role('SAMPLE_ROLE_1'));
        $user->addUserRole(new Role('SAMPLE_ROLE_2'));

        self::assertEquals(['SAMPLE_ROLE_1', 'SAMPLE_ROLE_2'], $user->getUserRoles());
    }

    public function testGetUserRoles(): void
    {
        $user = $this->getUser();

        $role1 = new Role('SAMPLE_ROLE_1');
        $role2 = new Role('SAMPLE_ROLE_2');
        $user->addUserRole($role1);
        $user->addUserRole($role2);

        self::assertEquals([$role1, $role2], $user->getUserRoles());
    }
}
