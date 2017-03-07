<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Twig;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Acl\Permission\PermissionManager;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;
use Oro\Bundle\SecurityBundle\Entity\Permission;
use Oro\Bundle\SecurityBundle\Model\AclPermission;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\SecurityBundle\Twig\OroSecurityExtension;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class OroSecurityExtensionTest extends \PHPUnit_Framework_TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $permissionManager;

    /** @var OroSecurityExtension */
    protected $extension;

    protected function setUp()
    {
        $this->securityFacade = $this->getMockBuilder(SecurityFacade::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->permissionManager = $this->getMockBuilder(PermissionManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $container = self::getContainerBuilder()
            ->add('oro_security.security_facade', $this->securityFacade)
            ->add('oro_security.acl.permission_manager', $this->permissionManager)
            ->getContainer($this);

        $this->extension = new OroSecurityExtension($container);
    }

    public function testGetName()
    {
        $this->assertEquals('oro_security_extension', $this->extension->getName());
    }

    public function testCheckResourceIsGranted()
    {
        $this->securityFacade->expects($this->once())
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
        $token = $this->createMock(TokenInterface::class);

        $this->securityFacade->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue($token));

        $token->expects($this->once())
            ->method('getUser')
            ->will($this->returnValue($user));

        $result = self::callTwigFunction($this->extension, 'get_enabled_organizations', []);

        $this->assertInternalType('array', $result);
        $this->assertCount(1, $result);
        $this->assertSame($organization, $result[0]);
    }

    public function testGetCurrentOrganizationWorks()
    {
        $organization = $this->createMock(Organization::class);

        $token = $this->createMock(OrganizationContextTokenInterface::class);

        $this->securityFacade->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue($token));

        $token->expects($this->once())
            ->method('getOrganizationContext')
            ->will($this->returnValue($organization));

        $this->assertSame(
            $organization,
            self::callTwigFunction($this->extension, 'get_current_organization', [])
        );
    }

    public function testGetCurrentOrganizationWorksWithNotOrganizationContextToken()
    {
        $token = $this->createMock(TokenInterface::class);

        $this->securityFacade->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue($token));

        $token->expects($this->never())->method($this->anything());

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
