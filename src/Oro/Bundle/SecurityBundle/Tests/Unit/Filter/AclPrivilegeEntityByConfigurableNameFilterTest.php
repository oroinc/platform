<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Filter;

use Oro\Bundle\SecurityBundle\Filter\AclPrivilegeEntityByConfigurableNameFilter;
use Oro\Bundle\SecurityBundle\Model\AclPermission;
use Oro\Bundle\SecurityBundle\Model\AclPrivilege;
use Oro\Bundle\SecurityBundle\Model\AclPrivilegeIdentity;
use Oro\Bundle\SecurityBundle\Model\ConfigurablePermission;

class AclPrivilegeEntityByConfigurableNameFilterTest extends \PHPUnit\Framework\TestCase
{
    private const CONFIGURABLE_NAME = 'default';
    private const ENTITY_CLASS = 'entity:Oro\Bundle\UserBundle\Entity\User';

    /** @var AclPrivilegeEntityByConfigurableNameFilter */
    private $filter;

    protected function setUp(): void
    {
        $this->filter = new AclPrivilegeEntityByConfigurableNameFilter(self::CONFIGURABLE_NAME, [self::ENTITY_CLASS]);
    }

    /**
     * @dataProvider isSupportedAclPrivilegeProvider
     */
    public function testIsSupported(AclPrivilege $aclPrivilege, bool $isSupported)
    {
        $this->assertSame($isSupported, $this->filter->isSupported($aclPrivilege));
    }

    public function isSupportedAclPrivilegeProvider(): array
    {
        return [
            'supported' => [
                'aclPrivilege' => (new AclPrivilege())
                    ->setIdentity(new AclPrivilegeIdentity('entity:' . self::ENTITY_CLASS)),
                'isSupported' => true
            ],
            'not supported' => [
                'aclPrivilege' => (new AclPrivilege())
                    ->setIdentity(new AclPrivilegeIdentity('entity:' . \stdClass::class)),
                'isSupported' => false
            ]
        ];
    }

    /**
     * @dataProvider filterDataProvider
     */
    public function testFilter(string $configurableName, bool $expected): void
    {
        $configurablePermission = $this->createMock(ConfigurablePermission::class);
        $configurablePermission->expects($this->any())
            ->method('getName')
            ->willReturn($configurableName);
        $configurablePermission->expects($this->any())
            ->method('isEntityPermissionConfigurable')
            ->willReturn(true);

        $aclPrivilege = (new AclPrivilege())->setIdentity(new AclPrivilegeIdentity('entity:test1'));
        $aclPrivilege->addPermission(new AclPermission('perm1'));

        $this->assertEquals($expected, $this->filter->filter($aclPrivilege, $configurablePermission));
    }

    public function filterDataProvider(): array
    {
        return [
            [self::CONFIGURABLE_NAME, false],
            ['test', true]
        ];
    }
}
