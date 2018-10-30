<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Twig;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Acl\Permission\PermissionManager;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\Entity\Permission;
use Oro\Bundle\SecurityBundle\Model\AclPermission;
use Oro\Bundle\SecurityBundle\Twig\OroSecurityExtension;
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

    /** @var OroSecurityExtension */
    protected $extension;

    protected function setUp()
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->permissionManager = $this->createMock(PermissionManager::class);

        $container = self::getContainerBuilder()
            ->add('security.authorization_checker', $this->authorizationChecker)
            ->add('oro_security.token_accessor', $this->tokenAccessor)
            ->add('oro_security.acl.permission_manager', $this->permissionManager)
            ->getContainer($this);

        $this->extension = new OroSecurityExtension($container);
    }

    public function testGetName()
    {
        $this->assertEquals('oro_security_extension', $this->extension->getName());
    }

    /**
     * @deprecated since 2.3. Use Symfony "is_granted" function instead
     */
    public function testCheckResourceIsGranted()
    {
        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with($this->equalTo('test_acl'))
            ->will($this->returnValue(true));
        $this->assertTrue(
            self::callTwigFunction($this->extension, 'resource_granted', ['test_acl'])
        );
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

        $this->assertInternalType('array', $result);
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
}
