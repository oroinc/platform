<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Entity;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

use Oro\Component\Testing\Unit\Entity\Stub\StubEnumValue;
use Oro\Bundle\EntityExtendBundle\Provider\EnumValueProvider;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Tests\Unit\Stub\UserStub as User;
use Oro\Bundle\UserBundle\Entity\UserApi;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class UserManagerTest extends \PHPUnit_Framework_TestCase
{
    const USER_CLASS = 'Oro\Bundle\UserBundle\Entity\User';

    /**
     * @var UserManager
     */
    protected $userManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ObjectManager
     */
    protected $om;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry
     */
    protected $registry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|EncoderFactoryInterface
     */
    protected $ef;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ClassMetadata
     */
    protected $metadata;

    protected function setUp()
    {
        if (!interface_exists('Doctrine\Common\Persistence\ObjectManager')) {
            $this->markTestSkipped('Doctrine Common has to be installed for this test to run.');
        }

        $this->ef = $this->getMock('Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface');
        $this->om = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->will($this->returnValue($this->om));

        $this->metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $this->om->expects($this->any())->method('getClassMetadata')->willReturn($this->metadata);

        /** @var EnumValueProvider $enumValueProvider */
        $enumValueProvider = $this->getMockBuilder(EnumValueProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $enumValueProvider->method('getEnumValueByCode')->willReturnCallback(
            function ($code, $id) {
                return new StubEnumValue($id, $id);
            }
        );

        $this->userManager = new UserManager(User::class, $this->registry, $this->ef, $enumValueProvider);
    }

    protected function tearDown()
    {
        unset($this->ef, $this->om, $this->registry, $this->userManager, $this->metadata);
    }

    public function testGetApi()
    {
        $user = new User();
        $organization = new Organization();
        $userApi = new UserApi();

        $repository = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\Repository\UserApiRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->om
            ->expects($this->any())
            ->method('getRepository')
            ->with('OroUserBundle:UserApi')
            ->will($this->returnValue($repository));

        $repository->expects($this->once())
            ->method('getApi')
            ->with($user, $organization)
            ->will($this->returnValue($userApi));

        $this->assertSame($userApi, $this->userManager->getApi($user, $organization));
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Default user role not found
     */
    public function testUpdateUserUnsupported()
    {
        $user = new User();

        $this->metadata->expects($this->once())->method('getAssociationTargetClass')
            ->willReturn('Symfony\Component\Security\Core\Role\RoleInterface');
        $this->om->expects($this->never())
            ->method('persist')
            ->with($this->equalTo($user));
        $this->om->expects($this->never())
            ->method('flush');
        $repository = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\Repository\UserApiRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->om
            ->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($repository));

        $this->userManager->updateUser($user);
    }

    public function testUpdateUser()
    {
        $password = 'password';
        $encodedPassword = 'encodedPassword';
        $email = 'test@test.com';

        $user = new User();
        $user
            ->setUsername($email)
            ->setEmail($email)
            ->setPlainPassword($password);

        $encoder = $this->getMock('Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface');
        $encoder->expects($this->once())
            ->method('encodePassword')
            ->with($user->getPlainPassword(), $user->getSalt())
            ->will($this->returnValue($encodedPassword));

        $this->ef->expects($this->once())
            ->method('getEncoder')
            ->with($user)
            ->will($this->returnValue($encoder));

        $this->om->expects($this->once())->method('persist')->with($this->equalTo($user));
        $this->om->expects($this->once())->method('flush');

        $this->metadata->expects($this->once())->method('getAssociationTargetClass')
            ->willReturn('Symfony\Component\Security\Core\Role\RoleInterface');
        $repository = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\Repository\UserApiRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->om
            ->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($repository));

        $repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with($this->equalTo(['role' => User::ROLE_DEFAULT]))
            ->will($this->returnValue(new Role(User::ROLE_DEFAULT)));

        $this->userManager->updateUser($user);

        $this->assertEquals($email, $user->getEmail());
        $this->assertEquals($encodedPassword, $user->getPassword());
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Expected Symfony\Component\Security\Core\Role\RoleInterface, \stdClass given
     */
    public function testNotSupportedRole()
    {
        $user = new User();

        $this->metadata->expects($this->once())->method('getAssociationTargetClass')
            ->willReturn('\stdClass');
        $this->om->expects($this->never())
            ->method('persist')
            ->with($this->equalTo($user));
        $this->om->expects($this->never())
            ->method('flush');
        $repository = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\Repository\UserApiRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->om
            ->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($repository));

        $this->userManager->updateUser($user);
    }

    public function testSetAuthStatus()
    {
        $user = new User();
        $this->assertNull($user->getAuthStatus());
        $this->userManager->setAuthStatus($user, UserManager::STATUS_EXPIRED);
        $this->assertEquals(UserManager::STATUS_EXPIRED, $user->getAuthStatus()->getId());
    }
}
