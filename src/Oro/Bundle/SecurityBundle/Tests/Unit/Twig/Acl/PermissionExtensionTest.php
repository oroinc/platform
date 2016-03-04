<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Twig\Acl;

use Oro\Bundle\SecurityBundle\Acl\Permission\PermissionManager;
use Oro\Bundle\SecurityBundle\Entity\Permission;
use Oro\Bundle\SecurityBundle\Model\AclPermission;
use Oro\Bundle\SecurityBundle\Twig\Acl\PermissionExtension;

class PermissionExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PermissionExtension
     */
    protected $twigExtension;

    /**
     * @var PermissionManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $permissionManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->permissionManager = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Acl\Permission\PermissionManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->twigExtension = new PermissionExtension($this->permissionManager);
    }

    public function testGetName()
    {
        $this->assertEquals(PermissionExtension::NAME, $this->twigExtension->getName());
    }

    public function testGetFunctions()
    {
        $expectedFunctions = ['acl_permission' => 'getPermission'];

        $actualFunctions = $this->twigExtension->getFunctions();
        $this->assertSameSize($expectedFunctions, $actualFunctions);

        foreach ($expectedFunctions as $twigFunction => $internalMethod) {
            $this->assertArrayHasKey($twigFunction, $actualFunctions);
            $this->assertInstanceOf('\Twig_Function_Method', $actualFunctions[$twigFunction]);
            $this->assertAttributeEquals($internalMethod, 'method', $actualFunctions[$twigFunction]);
        }
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

        $this->assertSame($permission, $this->twigExtension->getPermission($aclPermission));
    }
}
