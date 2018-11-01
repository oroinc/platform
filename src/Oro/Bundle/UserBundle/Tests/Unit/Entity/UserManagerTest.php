<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Entity;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Provider\EnumValueProvider;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\Repository\AbstractUserRepository;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\UserApi;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\UserBundle\Tests\Unit\Stub\UserStub as User;
use Oro\Component\Testing\Unit\Entity\Stub\StubEnumValue;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

class UserManagerTest extends \PHPUnit\Framework\TestCase
{
    const USER_CLASS = 'Oro\Bundle\UserBundle\Entity\User';

    /**
     * @var UserManager
     */
    protected $userManager;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ObjectManager
     */
    protected $om;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ManagerRegistry
     */
    protected $registry;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|EncoderFactoryInterface
     */
    protected $ef;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ClassMetadata
     */
    protected $metadata;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ConfigManager
     */
    protected $configManager;

    protected function setUp()
    {
        if (!interface_exists('Doctrine\Common\Persistence\ObjectManager')) {
            $this->markTestSkipped('Doctrine Common has to be installed for this test to run.');
        }

        $this->ef = $this->createMock('Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface');
        $this->om = $this->createMock('Doctrine\Common\Persistence\ObjectManager');
        $this->registry = $this->createMock('Doctrine\Common\Persistence\ManagerRegistry');
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

        $this->configManager = $this->createMock(ConfigManager::class);

        $this->userManager = new UserManager(
            User::class,
            $this->registry,
            $this->ef,
            $enumValueProvider,
            $this->configManager
        );
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

        $encoder = $this->createMock('Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface');
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

    public function testGeneratePasswordWithCustomLength()
    {
        $password = $this->userManager->generatePassword(10);
        $this->assertNotEmpty($password);
        $this->assertRegExp('/\w+/', $password);
        $this->assertLessThanOrEqual(10, strlen($password));
    }

    public function testGeneratePasswordWithDefaultLength()
    {
        $password = $this->userManager->generatePassword();
        $this->assertNotEmpty($password);
        $this->assertRegExp('/\w+/', $password);
        $this->assertLessThanOrEqual(30, strlen($password));
    }

    public function testFindUserByEmail()
    {
        $user = new User();
        $email = 'Test@test.com';

        $this->om
            ->expects(self::once())
            ->method('getRepository')
            ->with($this->userManager->getClass())
            ->willReturn($repository = $this->createMock(AbstractUserRepository::class));

        $repository
            ->expects(self::once())
            ->method('findUserByEmail')
            ->with($email, true)
            ->willReturn($user);

        $this->configManager
            ->expects(self::once())
            ->method('get')
            ->with('oro_user.case_insensitive_email_addresses_enabled')
            ->willReturn(true);

        $foundUser = $this->userManager->findUserByEmail($email);

        self::assertSame($user, $foundUser);
    }
}
