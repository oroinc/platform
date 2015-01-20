<?php

namespace Oro\Bundle\UserBundle\Tests\Entity;

use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;

use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;

class UserManagerTest extends \PHPUnit_Framework_TestCase
{
    const USER_CLASS = 'Oro\Bundle\UserBundle\Entity\User';
    const TEST_NAME  = 'Jack';
    const TEST_EMAIL = 'jack@jackmail.net';

    /**
     * @var User
     */
    protected $user;

    /**
     * @var UserManager
     */
    protected $userManager;

    protected $om;
    protected $repository;
    protected $cm;

    protected function setUp()
    {
        if (!interface_exists('Doctrine\Common\Persistence\ObjectManager')) {
            $this->markTestSkipped('Doctrine Common has to be installed for this test to run.');
        }

        $ef    = new EncoderFactory(array(static::USER_CLASS => new MessageDigestPasswordEncoder('sha512')));
        $class = $this->getMock('Doctrine\Common\Persistence\Mapping\ClassMetadata');

        $this->om         = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $this->repository = $this->getMock('Doctrine\Common\Persistence\ObjectRepository');
        $this->cm = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->om
            ->expects($this->any())
            ->method('getRepository')
            ->withAnyParameters()
            ->will($this->returnValue($this->repository));

        $this->om
            ->expects($this->any())
            ->method('getClassMetadata')
            ->with($this->equalTo(static::USER_CLASS))
            ->will($this->returnValue($class));

        $class->expects($this->any())
            ->method('getName')
            ->will($this->returnValue(static::USER_CLASS));

        $this->userManager = new UserManager(static::USER_CLASS, $this->om, $ef, $this->cm);
    }

    public function testGetClass()
    {
        $this->assertEquals(static::USER_CLASS, $this->userManager->getClass());
    }

    public function testCreateUser()
    {
        $this->assertInstanceof(static::USER_CLASS, $this->getUser());
    }

    public function testDeleteUser()
    {
        $user = $this->getUser();

        $this->om->expects($this->once())->method('remove')->with($this->equalTo($user));
        $this->om->expects($this->once())->method('flush');

        $this->userManager->deleteUser($user);
    }

    public function testUpdateUser()
    {
        $user = $this->getUser()
            ->setUsername(self::TEST_NAME)
            ->setEmail(self::TEST_EMAIL)
            ->setPlainPassword('password');

        $this->om->expects($this->once())->method('persist')->with($this->equalTo($user));
        $this->om->expects($this->once())->method('flush');

        $this->repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with($this->equalTo(array('role' => User::ROLE_DEFAULT)))
            ->will($this->returnValue(new Role(User::ROLE_DEFAULT)));

        $this->userManager->updateUser($user);

        $this->assertEquals(self::TEST_EMAIL, $user->getEmail());
    }

    public function testFindUserBy()
    {
        $crit = array('id' => 0);

        $this->repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with($this->equalTo($crit))
            ->will($this->returnValue(array()));

        $this->userManager->findUserBy($crit);
    }

    public function testFindUsers()
    {
        $this->repository
            ->expects($this->once())
            ->method('findAll')
            ->will($this->returnValue(array()));

        $this->userManager->findUsers();
    }

    public function testFindUserByUsername()
    {
        $crit = array('username' => self::TEST_NAME);

        $this->repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with($this->equalTo($crit))
            ->will($this->returnValue(array()));

        $this->userManager->findUserByUsernameOrEmail(self::TEST_NAME);
    }

    public function testFindUserByEmail()
    {
        $crit = array('email' => self::TEST_EMAIL);

        $this->repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with($this->equalTo($crit))
            ->will($this->returnValue(array()));

        $this->userManager->findUserByUsernameOrEmail(self::TEST_EMAIL);
    }

    public function testFindUserByToken()
    {
        $crit = array('confirmationToken' => self::TEST_NAME);

        $this->repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with($this->equalTo($crit))
            ->will($this->returnValue(array()));

        $this->userManager->findUserByConfirmationToken(self::TEST_NAME);
    }

    public function testReloadUser()
    {
        $user = $this->getUser();

        $this->om
            ->expects($this->once())
            ->method('refresh')
            ->with($this->equalTo($user));

        $this->userManager->reloadUser($user);
    }

    public function testRefreshUser()
    {
        $user = $this->getUser();
        $crit = array('id' => $user->getId());

        $this->repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with($this->equalTo($crit))
            ->will($this->returnValue(array()));

        $this->userManager->refreshUser($user);
    }

    /**
     * @expectedException Symfony\Component\Security\Core\Exception\UsernameNotFoundException
     */
    public function testLoadUserByUsername()
    {
        $crit = array('username' => self::TEST_NAME);

        $this->repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with($this->equalTo($crit))
            ->will($this->returnValue(array()));

        $this->userManager->loadUserByUsername(self::TEST_NAME);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage SSO is not enabled
     */
    public function testLoadUserByOAuthUserResponseShouldThrowExceptionIfSSOIsDisabled()
    {
        $this->cm
            ->expects($this->any())
            ->method('get')
            ->with($this->equalTo('oro_user.enable_google_sso'))
            ->will($this->returnValue(false));

        $userResponse = $this->getMock('HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface');

        $this->userManager->loadUserByOAuthUserResponse($userResponse);
    }

    public function testLoadUserByOAuthShouldReturnUserByOauthIdIfFound()
    {
        $this->cm
            ->expects($this->any())
            ->method('get')
            ->with($this->equalTo('oro_user.enable_google_sso'))
            ->will($this->returnValue(true));

        $userResponse = $this->getMock('HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface');
        $userResponse
            ->expects($this->any())
            ->method('getUsername')
            ->will($this->returnValue('username'));

        $userResponse
            ->expects($this->any())
            ->method('getResourceOwner')
            ->will($this->returnValue($this->getMock('HWI\Bundle\OAuthBundle\OAuth\ResourceOwnerInterface')));

        $userResponse
            ->expects($this->any())
            ->method('getEmail')
            ->will($this->returnValue('username@example.com'));

        $user = new User();
        $user->addRole(new Role());

        $this->repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with($this->equalTo(array('Id' => 'username')))
            ->will($this->returnValue($user))
        ;

        $loadedUser = $this->userManager->loadUserByOAuthUserResponse($userResponse);
        $this->assertSame($user, $loadedUser);
    }

    public function testLoadUserByOAuthShouldToFindUserByEmailIfLoadingByOauthIdFails()
    {
        $this->cm
            ->expects($this->at(0))
            ->method('get')
            ->with($this->equalTo('oro_user.enable_google_sso'))
            ->will($this->returnValue(true));

        $this->cm
            ->expects($this->at(1))
            ->method('get')
            ->with($this->equalTo('oro_user.google_sso_domains'))
            ->will($this->returnValue([]));

        $userResponse = $this->getMock('HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface');
        $userResponse
            ->expects($this->any())
            ->method('getUsername')
            ->will($this->returnValue('username'));

        $userResponse
            ->expects($this->any())
            ->method('getResourceOwner')
            ->will($this->returnValue($this->getMock('HWI\Bundle\OAuthBundle\OAuth\ResourceOwnerInterface')));

        $userResponse
            ->expects($this->any())
            ->method('getEmail')
            ->will($this->returnValue('username@example.com'));

        $this->repository
            ->expects($this->at(0))
            ->method('findOneBy')
            ->with($this->equalTo(array('Id' => 'username')))
        ;

        $user = new User();
        $user->addRole(new Role());

        $this->repository
            ->expects($this->at(1))
            ->method('findOneBy')
            ->with($this->equalTo(array('email' => 'username@example.com')))
            ->will($this->returnValue($user))
        ;

        $loadedUser = $this->userManager->loadUserByOAuthUserResponse($userResponse);
        $this->assertSame($user, $loadedUser);
    }

    public function testLoadUserByOAuthShouldFindUserByEmailWithRestrictedEmailDomainIfLoadingByOauthIdFails()
    {
        $this->cm
            ->expects($this->at(0))
            ->method('get')
            ->with($this->equalTo('oro_user.enable_google_sso'))
            ->will($this->returnValue(true));

        $this->cm
            ->expects($this->at(1))
            ->method('get')
            ->with($this->equalTo('oro_user.google_sso_domains'))
            ->will($this->returnValue(['example.com']));

        $userResponse = $this->getMock('HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface');
        $userResponse
            ->expects($this->any())
            ->method('getUsername')
            ->will($this->returnValue('username'));

        $userResponse
            ->expects($this->any())
            ->method('getResourceOwner')
            ->will($this->returnValue($this->getMock('HWI\Bundle\OAuthBundle\OAuth\ResourceOwnerInterface')));

        $userResponse
            ->expects($this->any())
            ->method('getEmail')
            ->will($this->returnValue('username@example.com'));

        $this->repository
            ->expects($this->at(0))
            ->method('findOneBy')
            ->with($this->equalTo(array('Id' => 'username')))
        ;

        $user = new User();
        $user->addRole(new Role());

        $this->repository
            ->expects($this->at(1))
            ->method('findOneBy')
            ->with($this->equalTo(array('email' => 'username@example.com')))
            ->will($this->returnValue($user))
        ;

        $loadedUser = $this->userManager->loadUserByOAuthUserResponse($userResponse);
        $this->assertSame($user, $loadedUser);
    }

    /**
     * @expectedException Oro\Bundle\UserBundle\Security\Core\Exception\EmailDomainNotAllowedException
     */
    public function testLoadUserByOAuthShouldThrowExceptionIfEmailDomainIsDisabled()
    {
        $this->cm
            ->expects($this->at(0))
            ->method('get')
            ->with($this->equalTo('oro_user.enable_google_sso'))
            ->will($this->returnValue(true));

        $this->cm
            ->expects($this->at(1))
            ->method('get')
            ->with($this->equalTo('oro_user.google_sso_domains'))
            ->will($this->returnValue(['google.com']));

        $userResponse = $this->getMock('HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface');
        $userResponse
            ->expects($this->any())
            ->method('getUsername')
            ->will($this->returnValue('username'));

        $userResponse
            ->expects($this->any())
            ->method('getResourceOwner')
            ->will($this->returnValue($this->getMock('HWI\Bundle\OAuthBundle\OAuth\ResourceOwnerInterface')));

        $this->userManager->loadUserByOAuthUserResponse($userResponse);
    }

    protected function getUser()
    {
        return $this->userManager->createUser();
    }
}
