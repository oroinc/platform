<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Authentication;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\ImpersonationToken;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationToken;
use Oro\Bundle\SecurityBundle\Authentication\TokenSerializer;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Role\RoleInterface;

class TokenSerializerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $doctrine;

    /** @var TokenSerializer */
    protected $tokenSerializer;

    protected function setUp()
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->tokenSerializer = new TokenSerializer($this->doctrine);
    }

    public function testSerializeForUnsupportedToken()
    {
        $token = $this->createMock(TokenInterface::class);

        self::assertNull($this->tokenSerializer->serialize($token));
    }

    public function testSerializeForTokenWithoutUser()
    {
        $organization = new Organization();
        $organization->setId(1);
        $token = new OrganizationToken($organization);

        self::assertNull($this->tokenSerializer->serialize($token));
    }

    public function testSerializeForTokenWithUnsupportedUser()
    {
        $organization = new Organization();
        $organization->setId(1);
        $token = new OrganizationToken($organization);
        $token->setUser('user');

        self::assertNull($this->tokenSerializer->serialize($token));
    }

    public function testSerializeForSupportedTokenWithoutRoles()
    {
        $organization = new Organization();
        $organization->setId(1);
        $user = $this->getMockBuilder(AbstractUser::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMockForAbstractClass();
        $user->expects(self::once())
            ->method('getId')
            ->willReturn(123);
        $token = new OrganizationToken($organization);
        $token->setUser($user);

        self::assertEquals(
            'organizationId=1;userId=123;userClass=' . get_class($user) . ';roles=',
            $this->tokenSerializer->serialize($token)
        );
    }

    public function testSerializeForSupportedTokenWithRoles()
    {
        $organization = new Organization();
        $organization->setId(1);
        $user = $this->getMockBuilder(AbstractUser::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMockForAbstractClass();
        $user->expects(self::once())
            ->method('getId')
            ->willReturn(123);
        $role1 = $this->createMock(RoleInterface::class);
        $role1->expects(self::once())
            ->method('getRole')
            ->willReturn('ROLE_1');
        $role2 = $this->createMock(RoleInterface::class);
        $role2->expects(self::once())
            ->method('getRole')
            ->willReturn('ROLE_2');
        $token = new OrganizationToken($organization, [$role1, $role2]);
        $token->setUser($user);

        self::assertEquals(
            'organizationId=1;userId=123;userClass=' . get_class($user) . ';roles=ROLE_1,ROLE_2',
            $this->tokenSerializer->serialize($token)
        );
    }

    public function testDeserializeSupportedToken()
    {
        $organization = new Organization();
        $organization->setId(1);
        $user = new User();
        $user->setId(123);

        $role1 = new Role('ROLE_1');
        $role2 = new Role('ROLE_2');
        $role3 = new Role('ROLE_3');
        $user->addRole($role1);
        $user->addRole($role2);
        $user->addRole($role3);

        $organizationRepo = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $userRepo = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrine->expects(self::exactly(2))
            ->method('getRepository')
            ->willReturnMap([
                [Organization::class, null, $organizationRepo],
                [User::class, null, $userRepo],
            ]);
        $organizationRepo->expects(self::once())
            ->method('find')
            ->with(1)
            ->willReturn($organization);
        $userRepo->expects(self::once())
            ->method('find')
            ->with(123)
            ->willReturn($user);

        /** @var ImpersonationToken $token */
        $token = $this->tokenSerializer->deserialize(
            'organizationId=1;userId=123;userClass=Oro\Bundle\UserBundle\Entity\User;roles=ROLE_1,ROLE_2'
        );

        self::assertInstanceOf(ImpersonationToken::class, $token);
        self::assertSame($organization, $token->getOrganizationContext());
        self::assertSame($user, $token->getUser());
        self::assertCount(2, $token->getRoles());
        self::assertSame($role1, $token->getRoles()[0]);
        self::assertSame($role2, $token->getRoles()[1]);
    }

    /**
     * @dataProvider unsupportedTokenProvider
     */
    public function testDeserializeUnsupportedToken($value)
    {
        self::assertNull($this->tokenSerializer->deserialize($value));
    }

    public function unsupportedTokenProvider()
    {
        return [
            [null],
            [''],
            ['organizationId=1'],
            ['organizationId=1;userId=123'],
            ['organizationId=1;userId=123;userClass=Oro\Bundle\UserBundle\Entity\User'],
            ['organizationId=1;userId=123;userClass=Oro\Bundle\UserBundle\Entity\User;roles=;'],
            ['organizationId=1;userId=123;userClass=Oro\Bundle\UserBundle\Entity\User;roles=ROLE_1;'],
            ['organizationId1=1;userId=123;userClass=Oro\Bundle\UserBundle\Entity\User;roles=ROLE_1'],
            ['organizationId=1;userId1=123;userClass=Oro\Bundle\UserBundle\Entity\User;roles=ROLE_1'],
            ['organizationId=1;userId=123;userClass1=Oro\Bundle\UserBundle\Entity\User;roles=ROLE_1'],
            ['organizationId=1;userId=123;userClass=Oro\Bundle\UserBundle\Entity\User;roles1=ROLE_1'],
        ];
    }

    public function testDeserializeSupportedTokenForDeletedUser()
    {
        $organization = new Organization();
        $organization->setId(1);

        $organizationRepo = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $userRepo = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrine->expects(self::exactly(2))
            ->method('getRepository')
            ->willReturnMap([
                [Organization::class, null, $organizationRepo],
                [User::class, null, $userRepo],
            ]);
        $organizationRepo->expects(self::once())
            ->method('find')
            ->with(1)
            ->willReturn($organization);
        $userRepo->expects(self::once())
            ->method('find')
            ->with(123)
            ->willReturn(null);

        /** @var ImpersonationToken $token */
        $token = $this->tokenSerializer->deserialize(
            'organizationId=1;userId=123;userClass=Oro\Bundle\UserBundle\Entity\User;roles=ROLE_1,ROLE_2'
        );

        self::assertNull($token);
    }

    public function testDeserializeSupportedTokenForDeletedOrganization()
    {
        $user = new User();
        $user->setId(123);

        $organizationRepo = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $userRepo = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrine->expects(self::exactly(2))
            ->method('getRepository')
            ->willReturnMap([
                [Organization::class, null, $organizationRepo],
                [User::class, null, $userRepo],
            ]);
        $organizationRepo->expects(self::once())
            ->method('find')
            ->with(1)
            ->willReturn(null);
        $userRepo->expects(self::once())
            ->method('find')
            ->with(123)
            ->willReturn($user);

        /** @var ImpersonationToken $token */
        $token = $this->tokenSerializer->deserialize(
            'organizationId=1;userId=123;userClass=Oro\Bundle\UserBundle\Entity\User;roles=ROLE_1,ROLE_2'
        );

        self::assertNull($token);
    }
}
