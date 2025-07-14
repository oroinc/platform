<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Filter;

use Oro\Bundle\SecurityBundle\Filter\AclPrivilegeCapabilityFilter;
use Oro\Bundle\SecurityBundle\Model\AclPrivilege;
use Oro\Bundle\SecurityBundle\Model\AclPrivilegeIdentity;
use Oro\Bundle\SecurityBundle\Model\ConfigurablePermission;
use PHPUnit\Framework\TestCase;

class AclPrivilegeCapabilityFilterTest extends TestCase
{
    private AclPrivilegeCapabilityFilter $filter;

    #[\Override]
    protected function setUp(): void
    {
        $this->filter = new AclPrivilegeCapabilityFilter();
    }

    /**
     * @dataProvider isSupportedAclPrivilegeProvider
     */
    public function testIsSupported(AclPrivilege $aclPrivilege, bool $isSupported): void
    {
        $this->assertSame($isSupported, $this->filter->isSupported($aclPrivilege));
    }

    public function isSupportedAclPrivilegeProvider(): array
    {
        return [
            'no supported' => [
                'aclPrivilege' => (new AclPrivilege())->setIdentity(new AclPrivilegeIdentity('entity:test')),
                'isSupported' => false
            ],
            'supported' => [
                'aclPrivilege' => (new AclPrivilege())->setIdentity(new AclPrivilegeIdentity('action:test')),
                'isSupported' => true
            ]
        ];
    }

    public function testFilter(): void
    {
        $aclPrivilege1 = (new AclPrivilege())->setIdentity(new AclPrivilegeIdentity('action:test1'));
        $aclPrivilege2 = (new AclPrivilege())->setIdentity(new AclPrivilegeIdentity('action:test2'));

        $configurablePermission = $this->createMock(ConfigurablePermission::class);
        $configurablePermission->expects($this->any())
            ->method('isCapabilityConfigurable')
            ->willReturnMap([
                ['test1', false],
                ['test2', true]
            ]);

        $this->assertTrue($this->filter->filter($aclPrivilege2, $configurablePermission));
        $this->assertFalse($this->filter->filter($aclPrivilege1, $configurablePermission));
    }
}
