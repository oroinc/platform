<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Twig;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\Repository\OrganizationRepository;
use Oro\Bundle\SecurityBundle\Acl\Permission\PermissionManager;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\Entity\Permission;
use Oro\Bundle\SecurityBundle\Model\AclPermission;
use Oro\Bundle\SecurityBundle\Twig\OroSecurityExtension;
use Oro\Bundle\SecurityBundle\Util\UriSecurityHelper;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use PHPUnit\Framework\MockObject\MockObject;

class OroSecurityExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var PermissionManager|\PHPUnit\Framework\MockObject\MockObject */
    private $permissionManager;

    /** @var UriSecurityHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $uriSecurityHelper;

    private ManagerRegistry&MockObject $doctrine;

    /** @var OroSecurityExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->permissionManager = $this->createMock(PermissionManager::class);
        $this->uriSecurityHelper = $this->createMock(UriSecurityHelper::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $container = self::getContainerBuilder()
            ->add(TokenAccessorInterface::class, $this->tokenAccessor)
            ->add('oro_security.acl.permission_manager', $this->permissionManager)
            ->add('oro_security.util.uri_security_helper', $this->uriSecurityHelper)
            ->add('doctrine', $this->doctrine)
            ->getContainer($this);

        $this->extension = new OroSecurityExtension($container);
    }

    public function testGetOrganizations()
    {
        $user = new User();
        $disabledOrganization = new Organization();
        $disabledOrganization->setEnabled(false);
        $organization = new Organization();
        $organization->setId(1);
        $organization->setName('org1');

        $organization->setEnabled(true);

        $user->setOrganizations(new ArrayCollection([$organization, $disabledOrganization]));

        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->assertSame(
            [
                ['id' => 1, 'name' => 'org1']
            ],
            self::callTwigFunction($this->extension, 'get_enabled_organizations', [])
        );
    }

    public function testGetUserOrganizationsCountWithoutUserInToken(): void
    {
        $this->tokenAccessor->expects(self::once())
            ->method('getUser')
            ->willReturn(null);

        self::assertEquals(0, self::callTwigFunction($this->extension, 'get_user_organizations_count', []));
    }

    public function testGetUserOrganizationsCount(): void
    {
        $user = new User();

        $this->tokenAccessor->expects(self::once())
            ->method('getUser')
            ->willReturn($user);

        $repository = $this->createMock(OrganizationRepository::class);
        $repository->expects(self::once())
            ->method('getUserOrganizationsCount')
            ->with($user)
            ->willReturn(23);

        $manager = $this->createMock(ObjectManager::class);
        $manager->expects(self::once())
            ->method('getRepository')
            ->willReturn($repository);

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(Organization::class)
            ->willReturn($manager);

        self::assertEquals(23, self::callTwigFunction($this->extension, 'get_user_organizations_count', []));
    }

    public function testGetCurrentOrganization()
    {
        $organization = $this->createMock(Organization::class);

        $this->tokenAccessor->expects($this->once())
            ->method('getOrganization')
            ->willReturn($organization);

        $this->assertSame(
            $organization,
            self::callTwigFunction($this->extension, 'get_current_organization', [])
        );
    }

    public function testGetCurrentOrganizationWhenNoOrganizationInToken()
    {
        $this->tokenAccessor->expects($this->once())
            ->method('getOrganization')
            ->willReturn(null);

        $this->assertNull(
            self::callTwigFunction($this->extension, 'get_current_organization', [])
        );
    }

    public function testGetPermission()
    {
        $aclPermission = new AclPermission();
        $aclPermission->setName('test name');

        $permission = new Permission();

        $this->permissionManager->expects($this->once())
            ->method('getPermissionByName')
            ->with('test name')
            ->willReturn($permission);

        $this->assertSame(
            $permission,
            self::callTwigFunction($this->extension, 'acl_permission', [$aclPermission])
        );
    }

    public function testStripDangerousProtocols(): void
    {
        $this->uriSecurityHelper->expects($this->once())
            ->method('stripDangerousProtocols')
            ->with($uri = 'sample-proto:sample-data')
            ->willReturn($expectedUri = 'sample-data');

        $this->assertEquals(
            $expectedUri,
            self::callTwigFilter($this->extension, 'strip_dangerous_protocols', [$uri])
        );
    }
}
