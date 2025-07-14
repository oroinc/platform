<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Twig;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Acl\Permission\PermissionManager;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\Entity\Permission;
use Oro\Bundle\SecurityBundle\Model\AclPermission;
use Oro\Bundle\SecurityBundle\Twig\OroSecurityExtension;
use Oro\Bundle\SecurityBundle\Util\UriSecurityHelper;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OroSecurityExtensionTest extends TestCase
{
    use TwigExtensionTestCaseTrait;

    private TokenAccessorInterface&MockObject $tokenAccessor;
    private PermissionManager&MockObject $permissionManager;
    private UriSecurityHelper&MockObject $uriSecurityHelper;
    private OroSecurityExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->permissionManager = $this->createMock(PermissionManager::class);
        $this->uriSecurityHelper = $this->createMock(UriSecurityHelper::class);

        $container = self::getContainerBuilder()
            ->add(TokenAccessorInterface::class, $this->tokenAccessor)
            ->add('oro_security.acl.permission_manager', $this->permissionManager)
            ->add('oro_security.util.uri_security_helper', $this->uriSecurityHelper)
            ->getContainer($this);

        $this->extension = new OroSecurityExtension($container);
    }

    public function testGetOrganizations(): void
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

    public function testGetCurrentOrganization(): void
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

    public function testGetCurrentOrganizationWhenNoOrganizationInToken(): void
    {
        $this->tokenAccessor->expects($this->once())
            ->method('getOrganization')
            ->willReturn(null);

        $this->assertNull(
            self::callTwigFunction($this->extension, 'get_current_organization', [])
        );
    }

    public function testGetPermission(): void
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
