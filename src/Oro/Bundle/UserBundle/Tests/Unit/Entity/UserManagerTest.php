<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Entity;

use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
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
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $om;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $ef;

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

        $this->userManager = new UserManager(static::USER_CLASS, $this->registry, $this->ef);
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
            ->with('OroUserBundle:Role')
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

        $repository = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\Repository\UserApiRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->om
            ->expects($this->any())
            ->method('getRepository')
            ->with('OroUserBundle:Role')
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
}
