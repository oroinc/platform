<?php

namespace Oro\Bundle\SSOBundle\Tests\Entity;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Provider\EnumValueProvider;
use Oro\Bundle\SSOBundle\Security\Core\User\OAuthUserProvider;
use Oro\Bundle\SSOBundle\Tests\Unit\Stub\TestingUser;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Component\Testing\Unit\Entity\Stub\StubEnumValue;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;

class OAuthUserProviderTest extends \PHPUnit\Framework\TestCase
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

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $om;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $repository;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $cm;

    /**
     * @var OAuthUserProvider
     */
    protected $oauthProvider;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $registry;

    protected function setUp()
    {
        if (!interface_exists('Doctrine\Common\Persistence\ObjectManager')) {
            $this->markTestSkipped('Doctrine Common has to be installed for this test to run.');
        }

        $ef    = new EncoderFactory([static::USER_CLASS => new MessageDigestPasswordEncoder('sha512')]);
        $class = $this->createMock('Doctrine\Common\Persistence\Mapping\ClassMetadata');

        $this->om         = $this->createMock('Doctrine\Common\Persistence\ObjectManager');
        $this->repository = $this->createMock('Doctrine\Common\Persistence\ObjectRepository');
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


        $this->registry = $this->createMock('Doctrine\Common\Persistence\ManagerRegistry');
        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->will($this->returnValue($this->om));

        $class->expects($this->any())
            ->method('getName')
            ->will($this->returnValue(static::USER_CLASS));
        /** @var EnumValueProvider|\PHPUnit\Framework\MockObject\MockObject $enumValueProvider */
        $enumValueProvider = $this->getMockBuilder(EnumValueProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $enumValueProvider->method('getDefaultEnumValuesByCode')->willReturn(
            new StubEnumValue('active', 'active', 0, true)
        );
        $enumValueProvider->method('getEnumValueByCode')->willReturnCallback(
            function ($code, $id) {
                return new StubEnumValue($id, $id, 0, false);
            }
        );
        $this->userManager = new UserManager(
            static::USER_CLASS,
            $this->registry,
            $ef,
            $enumValueProvider,
            $this->createMock(ConfigManager::class)
        );

        $this->oauthProvider = new OAuthUserProvider($this->userManager, $this->cm);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage SSO is not enabled
     */
    public function testLoadUserByOAuthUserResponseShouldThrowExceptionIfSSOIsDisabled()
    {
        $this->cm
            ->expects($this->any())
            ->method('get')
            ->with($this->equalTo('oro_sso.enable_google_sso'))
            ->will($this->returnValue(false));

        $userResponse = $this->createMock('HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface');

        $this->oauthProvider->loadUserByOAuthUserResponse($userResponse);
    }

    public function testLoadUserByOAuthShouldReturnUserByOauthIdIfFound()
    {
        $this->cm
            ->expects($this->at(0))
            ->method('get')
            ->with($this->equalTo('oro_sso.enable_google_sso'))
            ->will($this->returnValue(true));

        $this->cm
            ->expects($this->at(1))
            ->method('get')
            ->with($this->equalTo('oro_sso.domains'))
            ->will($this->returnValue([]));

        $userResponse = $this->createMock('HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface');
        $userResponse
            ->expects($this->any())
            ->method('getUsername')
            ->will($this->returnValue('username'));

        $userResponse
            ->expects($this->any())
            ->method('getResourceOwner')
            ->will($this->returnValue($this->createMock('HWI\Bundle\OAuthBundle\OAuth\ResourceOwnerInterface')));

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

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\BadCredentialsException
     */
    public function testLoadUserByOAuthShouldReturnExceptionIfUserIsDisabled()
    {
        $this->cm
            ->expects($this->at(0))
            ->method('get')
            ->with($this->equalTo('oro_sso.enable_google_sso'))
            ->will($this->returnValue(true));

        $this->cm
            ->expects($this->at(1))
            ->method('get')
            ->with($this->equalTo('oro_sso.domains'))
            ->will($this->returnValue([]));

        $userResponse = $this->createMock('HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface');
        $userResponse
            ->expects($this->any())
            ->method('getUsername')
            ->will($this->returnValue('username'));

        $userResponse
            ->expects($this->any())
            ->method('getResourceOwner')
            ->will($this->returnValue($this->createMock('HWI\Bundle\OAuthBundle\OAuth\ResourceOwnerInterface')));

        $userResponse
            ->expects($this->any())
            ->method('getEmail')
            ->will($this->returnValue('username@example.com'));

        $user = new TestingUser();
        $user->addRole(new Role());
        $user->setEnabled(false);

        $this->repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with($this->equalTo(['Id' => 'username']))
            ->will($this->returnValue($user))
        ;

        $this->oauthProvider->loadUserByOAuthUserResponse($userResponse);
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
            ->with($this->equalTo('oro_sso.domains'))
            ->will($this->returnValue([]));

        $userResponse = $this->createMock('HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface');
        $userResponse
            ->expects($this->any())
            ->method('getUsername')
            ->will($this->returnValue('username'));

        $userResponse
            ->expects($this->any())
            ->method('getResourceOwner')
            ->will($this->returnValue($this->createMock('HWI\Bundle\OAuthBundle\OAuth\ResourceOwnerInterface')));

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
            ->with($this->equalTo('oro_sso.domains'))
            ->will($this->returnValue(['example.com']));

        $userResponse = $this->createMock('HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface');
        $userResponse
            ->expects($this->any())
            ->method('getUsername')
            ->will($this->returnValue('username'));

        $userResponse
            ->expects($this->any())
            ->method('getResourceOwner')
            ->will($this->returnValue($this->createMock('HWI\Bundle\OAuthBundle\OAuth\ResourceOwnerInterface')));

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
     * @expectedException \Oro\Bundle\SSOBundle\Security\Core\Exception\EmailDomainNotAllowedException
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
            ->with($this->equalTo('oro_sso.domains'))
            ->will($this->returnValue(['google.com']));

        $userResponse = $this->createMock('HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface');
        $userResponse
            ->expects($this->any())
            ->method('getUsername')
            ->will($this->returnValue('username'));

        $userResponse
            ->expects($this->any())
            ->method('getResourceOwner')
            ->will($this->returnValue($this->createMock('HWI\Bundle\OAuthBundle\OAuth\ResourceOwnerInterface')));

        $this->repository
            ->expects($this->never())
            ->method('findOneBy')
        ;

        $this->oauthProvider->loadUserByOAuthUserResponse($userResponse);
    }
}
