<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Filter;

use Oro\Bundle\SecurityBundle\Filter\AclPrivilegeEntityFilter;
use Oro\Bundle\SecurityBundle\Model\AclPermission;
use Oro\Bundle\SecurityBundle\Model\AclPrivilege;
use Oro\Bundle\SecurityBundle\Model\AclPrivilegeIdentity;
use Oro\Bundle\SecurityBundle\Model\ConfigurablePermission;

class AclPrivilegeEntityFilterTest extends AbstractAclPrivilegeFilterTestCase
{
    /**
     * @return \Generator
     */
    public function isSupportedAclPrivilegeProvider()
    {
        yield 'supported' => [
            'aclPrivilege' => (new AclPrivilege())->setIdentity(new AclPrivilegeIdentity('entity:test')),
            'isSupported' => true
        ];

        yield 'not supported' => [
            'aclPrivilege' => (new AclPrivilege())->setIdentity(new AclPrivilegeIdentity('config:test')),
            'isSupported' => false
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function createFilter()
    {
        return new AclPrivilegeEntityFilter();
    }

    public function testFilter()
    {
        $aclPrivilege1 = (new AclPrivilege())->setIdentity(new AclPrivilegeIdentity('entity:test1'));
        $aclPrivilege2 = (new AclPrivilege())->setIdentity(new AclPrivilegeIdentity('entity:test2'));

        $aclPrivilege1->addPermission(new AclPermission('perm1'));
        $aclPrivilege1->addPermission(new AclPermission('perm2'));
        $aclPrivilege2->addPermission(new AclPermission('perm2'));

        /** @var ConfigurablePermission|\PHPUnit\Framework\MockObject\MockObject $configurablePermission */
        $configurablePermission = $this->createMock(ConfigurablePermission::class);
        $configurablePermission->expects($this->any())
            ->method('isEntityPermissionConfigurable')
            ->willReturnMap(
                [
                    ['test1','perm1', false],
                    ['test1','perm2', true],
                    ['test2','perm2', false]
                ]
            );

        $this->assertTrue($this->filter->filter($aclPrivilege1, $configurablePermission));
        $this->assertCount(1, $aclPrivilege1->getPermissions());
        $this->assertFalse($this->filter->filter($aclPrivilege2, $configurablePermission));
    }
}
