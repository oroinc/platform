<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Filter;

use Oro\Bundle\SecurityBundle\Filter\AclPrivilegeEntityByConfigurableNameFilter;
use Oro\Bundle\SecurityBundle\Model\AclPermission;
use Oro\Bundle\SecurityBundle\Model\AclPrivilege;
use Oro\Bundle\SecurityBundle\Model\AclPrivilegeIdentity;
use Oro\Bundle\SecurityBundle\Model\ConfigurablePermission;

class AclPrivilegeEntityByConfigurableNameFilterTest extends AbstractAclPrivilegeFilterTestCase
{
    private const CONFIGURABLE_NAME = 'default';
    private const ENTITY_CLASS = 'entity:Oro\Bundle\UserBundle\Entity\User';

    /**
     * {@inheritdoc}
     */
    public function isSupportedAclPrivilegeProvider()
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
     * {@inheritdoc}
     */
    protected function createFilter()
    {
        return new AclPrivilegeEntityByConfigurableNameFilter(self::CONFIGURABLE_NAME, [self::ENTITY_CLASS]);
    }

    /**
     * @dataProvider filterDataProvider
     *
     * @param string $configurableName
     * @param bool $expected
     */
    public function testFilter(string $configurableName, bool $expected): void
    {
        /** @var ConfigurablePermission|\PHPUnit_Framework_MockObject_MockObject $configurablePermission */
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

    /**
     * @return array
     */
    public function filterDataProvider(): array
    {
        return [
            [self::CONFIGURABLE_NAME, false],
            ['test', true]
        ];
    }
}
