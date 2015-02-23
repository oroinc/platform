<?php

namespace Oro\Bundle\UserBundle\Tests\Entity;

use Oro\Bundle\SSOBundle\Security\Core\Exception\EmailDomainNotAllowedException;
use Oro\Bundle\SSOBundle\Security\Core\User\OAuthUserProvider;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User as OroUser;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;
use Symfony\Component\Serializer\Exception\Exception;

class OAuthUserProviderTest extends \PHPUnit_Framework_TestCase
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

    protected $oauthProvider;

    protected function setUp()
    {
        if (!interface_exists('Doctrine\Common\Persistence\ObjectManager')) {
            $this->markTestSkipped('Doctrine Common has to be installed for this test to run.');
        }

        $ef    = new EncoderFactory([static::USER_CLASS => new MessageDigestPasswordEncoder('sha512')]);
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

        $this->userManager = new UserManager(static::USER_CLASS, $this->om, $ef);
        
        $this->oauthProvider = new OAuthUserProvider($this->userManager, $this->cm);
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
            ->with($this->equalTo('oro_sso.enable_google_sso'))
            ->will($this->returnValue(false));

        $userResponse = $this->getMock('HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface');

        $this->oauthProvider->loadUserByOAuthUserResponse($userResponse);
    }

    public function testLoadUserByOAuthShouldReturnUserByOauthIdIfFound()
    {
        $this->cm
            ->expects($this->any())
            ->method('get')
            ->with($this->equalTo('oro_sso.enable_google_sso'))
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

        $user = new TestingUser();
        $user->addRole(new Role());

        $this->repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with($this->equalTo(['Id' => 'username']))
            ->will($this->returnValue($user))
        ;

        $loadedUser = $this->oauthProvider->loadUserByOAuthUserResponse($userResponse);
        $this->assertSame($user, $loadedUser);
    }

    public function testLoadUserByOAuthShouldToFindUserByEmailIfLoadingByOauthIdFails()
    {
        $this->cm
            ->expects($this->at(0))
            ->method('get')
            ->with($this->equalTo('oro_sso.enable_google_sso'))
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
            ->with($this->equalTo(['Id' => 'username']))
        ;

        $user = new TestingUser();
        $user->addRole(new Role());

        $this->repository
            ->expects($this->at(1))
            ->method('findOneBy')
            ->with($this->equalTo(['email' => 'username@example.com']))
            ->will($this->returnValue($user))
        ;

        $loadedUser = $this->oauthProvider->loadUserByOAuthUserResponse($userResponse);
        $this->assertSame($user, $loadedUser);
    }

    public function testLoadUserByOAuthShouldFindUserByEmailWithRestrictedEmailDomainIfLoadingByOauthIdFails()
    {
        $this->cm
            ->expects($this->at(0))
            ->method('get')
            ->with($this->equalTo('oro_sso.enable_google_sso'))
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
            ->with($this->equalTo(['Id' => 'username']))
        ;

        $user = new TestingUser();
        $user->addRole(new Role());

        $this->repository
            ->expects($this->at(1))
            ->method('findOneBy')
            ->with($this->equalTo(['email' => 'username@example.com']))
            ->will($this->returnValue($user))
        ;

        $loadedUser = $this->oauthProvider->loadUserByOAuthUserResponse($userResponse);
        $this->assertSame($user, $loadedUser);
    }

    /**
     * @expectedException Oro\Bundle\SSOBundle\Security\Core\Exception\EmailDomainNotAllowedException
     */
    public function testLoadUserByOAuthShouldThrowExceptionIfEmailDomainIsDisabled()
    {
        $this->cm
            ->expects($this->at(0))
            ->method('get')
            ->with($this->equalTo('oro_sso.enable_google_sso'))
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

        $this->oauthProvider->loadUserByOAuthUserResponse($userResponse);
    }
}

class TestingUser extends OroUser
{
    /**
     * This method is generated by entity extend bundle
     */
    public function setGoogleId($id)
    {
        
    }
}
