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
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class OroSecurityExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $authorizationChecker;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $tokenAccessor;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $permissionManager;

    /** @var UriSecurityHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $uriSecurityHelper;

    /** @var OroSecurityExtension */
    protected $extension;

    protected function setUp(): void
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->permissionManager = $this->createMock(PermissionManager::class);
        $this->uriSecurityHelper = $this->createMock(UriSecurityHelper::class);

        $container = self::getContainerBuilder()
            ->add(AuthorizationCheckerInterface::class, $this->authorizationChecker)
            ->add(TokenAccessorInterface::class, $this->tokenAccessor)
            ->add(PermissionManager::class, $this->permissionManager)
            ->add(UriSecurityHelper::class, $this->uriSecurityHelper)
            ->getContainer($this);

        $this->extension = new OroSecurityExtension($container);
    }

    public function testGetName()
    {
        $this->assertEquals('oro_security_extension', $this->extension->getName());
    }

    public function testGetOrganizations()
    {
        $user = new User();
        $disabledOrganization = new Organization();
        $organization = new Organization();

        $organization->setEnabled(true);

        $user->setOrganizations(new ArrayCollection(array($organization, $disabledOrganization)));

        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->will($this->returnValue($user));

        $result = self::callTwigFunction($this->extension, 'get_enabled_organizations', []);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertSame($organization, $result[0]);
    }

    public function testGetCurrentOrganization()
    {
        $organization = $this->createMock(Organization::class);

        $this->tokenAccessor->expects($this->once())
            ->method('getOrganization')
            ->will($this->returnValue($organization));

        $this->assertSame(
            $organization,
            self::callTwigFunction($this->extension, 'get_current_organization', [])
        );
    }

    public function testGetCurrentOrganizationWhenNoOrganizationInToken()
    {
        $this->tokenAccessor->expects($this->once())
            ->method('getOrganization')
            ->will($this->returnValue(null));

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
        $this->uriSecurityHelper
            ->expects($this->once())
            ->method('stripDangerousProtocols')
            ->with($uri = 'sample-proto:sample-data')
            ->willReturn($expectedUri = 'sample-data');

        $this->assertEquals(
            $expectedUri,
            self::callTwigFilter($this->extension, 'strip_dangerous_protocols', [$uri])
        );
    }
}
