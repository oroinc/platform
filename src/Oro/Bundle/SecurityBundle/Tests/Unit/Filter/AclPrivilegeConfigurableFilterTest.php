<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Filter;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\SecurityBundle\Acl\Permission\ConfigurablePermissionProvider;
use Oro\Bundle\SecurityBundle\Filter\AclPrivilegeConfigurableFilter;
use Oro\Bundle\SecurityBundle\Filter\AclPrivilegeConfigurableFilterInterface;
use Oro\Bundle\SecurityBundle\Model\AclPrivilege;
use Oro\Bundle\SecurityBundle\Model\ConfigurablePermission;

class AclPrivilegeConfigurableFilterTest extends \PHPUnit\Framework\TestCase
{
    public function testFilterWhenNoFilters()
    {
        $configurableName = 'test';
        $configurablePermission = $this->createMock(ConfigurablePermission::class);

        $aclPrivilege = new AclPrivilege();
        $aclPrivileges = new ArrayCollection([$aclPrivilege]);
        $expected = new ArrayCollection([$aclPrivilege]);

        $permissionProvider = $this->createMock(ConfigurablePermissionProvider::class);
        $permissionProvider->expects(self::any())
            ->method('get')
            ->with($configurableName)
            ->willReturn($configurablePermission);

        $filter = new AclPrivilegeConfigurableFilter([], $permissionProvider);
        $result = $filter->filter($aclPrivileges, $configurableName);

        self::assertEquals($expected->toArray(), $result->toArray());
        self::assertNotSame($aclPrivileges, $result);
    }

    public function testFilterWhenNoFiltersThatSupportFiltering()
    {
        $configurableName = 'test';
        $configurablePermission = $this->createMock(ConfigurablePermission::class);

        $aclPrivilege = new AclPrivilege();
        $aclPrivileges = new ArrayCollection([$aclPrivilege]);
        $expected = new ArrayCollection([$aclPrivilege]);

        $filter = $this->createMock(AclPrivilegeConfigurableFilterInterface::class);
        $filter->expects(self::once())
            ->method('isSupported')
            ->with(self::identicalTo($aclPrivilege))
            ->willReturn(false);
        $filter->expects(self::never())
            ->method('filter');

        $permissionProvider = $this->createMock(ConfigurablePermissionProvider::class);
        $permissionProvider->expects(self::any())
            ->method('get')
            ->with($configurableName)
            ->willReturn($configurablePermission);

        $filter = new AclPrivilegeConfigurableFilter([$filter], $permissionProvider);
        $result = $filter->filter($aclPrivileges, $configurableName);

        self::assertEquals($expected->toArray(), $result->toArray());
        self::assertNotSame($aclPrivileges, $result);
    }

    public function testFilterWhenFiltersThatSupportFilteringExist()
    {
        $configurableName = 'test';
        $configurablePermission = $this->createMock(ConfigurablePermission::class);

        $aclPrivilege = new AclPrivilege();
        $aclPrivileges = new ArrayCollection([$aclPrivilege]);
        $expected = new ArrayCollection([$aclPrivilege]);

        $filter = $this->createMock(AclPrivilegeConfigurableFilterInterface::class);
        $filter->expects(self::once())
            ->method('isSupported')
            ->with(self::identicalTo($aclPrivilege))
            ->willReturn(true);
        $filter->expects(self::once())
            ->method('filter')
            ->with(self::identicalTo($aclPrivilege))
            ->willReturn(true);

        $permissionProvider = $this->createMock(ConfigurablePermissionProvider::class);
        $permissionProvider->expects(self::any())
            ->method('get')
            ->with($configurableName)
            ->willReturn($configurablePermission);

        $filter = new AclPrivilegeConfigurableFilter([$filter], $permissionProvider);
        $result = $filter->filter($aclPrivileges, $configurableName);

        self::assertEquals($expected->toArray(), $result->toArray());
        self::assertNotSame($aclPrivileges, $result);
    }

    public function testFilterWhenFiltersThatSupportFilteringExistAndFilterReturnsFalse()
    {
        $configurableName = 'test';
        $configurablePermission = $this->createMock(ConfigurablePermission::class);

        $aclPrivilege = new AclPrivilege();
        $aclPrivileges = new ArrayCollection([$aclPrivilege]);
        $expected = new ArrayCollection();

        $filter = $this->createMock(AclPrivilegeConfigurableFilterInterface::class);
        $filter->expects(self::once())
            ->method('isSupported')
            ->with(self::identicalTo($aclPrivilege))
            ->willReturn(true);
        $filter->expects(self::once())
            ->method('filter')
            ->with(self::identicalTo($aclPrivilege))
            ->willReturn(false);

        $permissionProvider = $this->createMock(ConfigurablePermissionProvider::class);
        $permissionProvider->expects(self::any())
            ->method('get')
            ->with($configurableName)
            ->willReturn($configurablePermission);

        $filter = new AclPrivilegeConfigurableFilter([$filter], $permissionProvider);
        $result = $filter->filter($aclPrivileges, $configurableName);

        self::assertEquals($expected->toArray(), $result->toArray());
        self::assertNotSame($aclPrivileges, $result);
    }
}
