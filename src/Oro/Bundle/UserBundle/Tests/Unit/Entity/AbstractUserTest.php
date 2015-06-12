<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Tests\Unit\Stub\AbstractUserStub;

class AbstractUserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return AbstractUser
     */
    public function getUser()
    {
        return new AbstractUserStub();
    }

    public function testUsername()
    {
        $user = $this->getUser();
        $name = 'Tony';

        $this->assertNull($user->getUsername());

        $user->setUsername($name);

        $this->assertEquals($name, $user->getUsername());
        $this->assertEquals($name, $user);
    }

    public function testIsPasswordRequestNonExpired()
    {
        $user = $this->getUser();
        $requested = new \DateTime('-10 seconds');

        $user->setPasswordRequestedAt($requested);

        $this->assertSame($requested, $user->getPasswordRequestedAt());
        $this->assertTrue($user->isPasswordRequestNonExpired(15));
        $this->assertFalse($user->isPasswordRequestNonExpired(5));
    }

    public function testIsPasswordRequestAtCleared()
    {
        $user = $this->getUser();
        $requested = new \DateTime('-10 seconds');

        $user->setPasswordRequestedAt($requested);
        $user->setPasswordRequestedAt(null);

        $this->assertFalse($user->isPasswordRequestNonExpired(15));
        $this->assertFalse($user->isPasswordRequestNonExpired(5));
    }

    public function testConfirmationToken()
    {
        $user = $this->getUser();
        $token = $user->generateToken();

        $this->assertNotEmpty($token);

        $user->setConfirmationToken($token);

        $this->assertEquals($token, $user->getConfirmationToken());
    }

    public function testSetRolesWithArrayArgument()
    {
        $roles = [new Role(User::ROLE_DEFAULT)];
        $user = $this->getUser();
        $this->assertEmpty($user->getRoles());
        $user->setRoles($roles);
        $this->assertEquals($roles, $user->getRoles());
    }

    public function testSetRolesWithCollectionArgument()
    {
        $roles = new ArrayCollection([new Role(User::ROLE_DEFAULT)]);
        $user = $this->getUser();
        $this->assertEmpty($user->getRoles());
        $user->setRoles($roles);
        $this->assertEquals($roles->toArray(), $user->getRoles());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage $roles must be an instance of Doctrine\Common\Collections\Collection or an array
     */
    public function testSetRolesThrowsInvalidArgumentException()
    {
        $user = $this->getUser();
        $user->setRoles('roles');
    }

    public function testHasRoleWithStringArgument()
    {
        $user = $this->getUser();
        $role = new Role(User::ROLE_DEFAULT);

        $this->assertFalse($user->hasRole(User::ROLE_DEFAULT));
        $user->addRole($role);
        $this->assertTrue($user->hasRole(User::ROLE_DEFAULT));
    }

    public function testHasRoleWithObjectArgument()
    {
        $user = $this->getUser();
        $role = new Role(User::ROLE_DEFAULT);

        $this->assertFalse($user->hasRole($role));
        $user->addRole($role);
        $this->assertTrue($user->hasRole($role));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage $role must be an instance of Oro\Bundle\UserBundle\Entity\AbstractRole or a string
     */
    public function testHasRoleThrowsInvalidArgumentException()
    {
        $user = $this->getUser();
        $user->hasRole(new \stdClass());
    }

    public function testRemoveRoleWithStringArgument()
    {
        $user = $this->getUser();
        $role = new Role(User::ROLE_DEFAULT);
        $user->addRole($role);

        $this->assertTrue($user->hasRole($role));
        $user->removeRole(User::ROLE_DEFAULT);
        $this->assertFalse($user->hasRole($role));
    }

    public function testRemoveRoleWithObjectArgument()
    {
        $user = $this->getUser();
        $role = new Role(User::ROLE_DEFAULT);
        $user->addRole($role);

        $this->assertTrue($user->hasRole($role));
        $user->removeRole($role);
        $this->assertFalse($user->hasRole($role));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage $role must be an instance of Oro\Bundle\UserBundle\Entity\AbstractRole or a string
     */
    public function testRemoveRoleThrowsInvalidArgumentException()
    {
        $user = $this->getUser();
        $user->removeRole(new \stdClass());
    }

    public function testIsEnabled()
    {
        $user = $this->getUser();

        $this->assertTrue($user->isEnabled());
        $this->assertTrue($user->isAccountNonExpired());
        $this->assertTrue($user->isAccountNonLocked());

        $user->setEnabled(false);

        $this->assertFalse($user->isEnabled());
        $this->assertFalse($user->isAccountNonLocked());
    }

    public function testSerializing()
    {
        $user = $this->getUser();
        $clone = clone $user;
        $data = $user->serialize();

        $this->assertNotEmpty($data);

        $user
            ->setPassword('new-pass')
            ->setConfirmationToken('token')
            ->setUsername('new-name');

        $user->unserialize($data);

        $this->assertEquals($clone, $user);
    }

    public function testPassword()
    {
        $user = $this->getUser();
        $pass = 'anotherPassword';

        $user->setPassword($pass);
        $user->setPlainPassword($pass);

        $this->assertEquals($pass, $user->getPassword());
        $this->assertEquals($pass, $user->getPlainPassword());

        $user->eraseCredentials();

        $this->assertNull($user->getPlainPassword());
    }

    public function testUnserialize()
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
        $user->unserialize(serialize($serialized));

        $this->assertEquals($serialized[0], $user->getPassword());
        $this->assertEquals($serialized[1], $user->getSalt());
        $this->assertEquals($serialized[2], $user->getUsername());
        $this->assertEquals($serialized[3], $user->isEnabled());
        $this->assertEquals($serialized[4], $user->getConfirmationToken());
        $this->assertEquals($serialized[5], $user->getId());
    }

    public function testIsCredentialsNonExpired()
    {
        $user = $this->getUser();
        $this->assertTrue($user->isCredentialsNonExpired());
    }

    /**
     * @dataProvider provider
     * @param string $property
     * @param mixed $value
     */
    public function testSettersAndGetters($property, $value)
    {
        $obj = $this->getUser();

        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $propertyAccessor->setValue($obj, $property, $value);
        $this->assertEquals($value, $propertyAccessor->getValue($obj, $property));
    }

    /**
     * Data provider
     *
     * @return array
     */
    public function provider()
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

    public function testOrganization()
    {
        $entity = $this->getUser();
        $organization = new Organization();

        $this->assertNull($entity->getOrganization());
        $entity->setOrganization($organization);
        $this->assertSame($organization, $entity->getOrganization());
    }

    public function testOrganizations()
    {
        $user = $this->getUser();
        $disabledOrganization = new Organization();
        $organization = new Organization();
        $organization->setEnabled(true);

        $user->setOrganizations(new ArrayCollection([$organization]));
        $this->assertContains($organization, $user->getOrganizations());

        $user->removeOrganization($organization);
        $this->assertNotContains($organization, $user->getOrganizations());

        $user->addOrganization($organization);
        $this->assertContains($organization, $user->getOrganizations());

        $user->addOrganization($disabledOrganization);
        $result = $user->getOrganizations(true);
        $this->assertCount(1, $result);
        $this->assertSame($result->first(), $organization);
    }
}
