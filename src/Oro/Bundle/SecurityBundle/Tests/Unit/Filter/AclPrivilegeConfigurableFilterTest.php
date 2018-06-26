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
    /** @var AclPrivilegeConfigurableFilter */
    protected $filter;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        /** @var ConfigurablePermissionProvider|\PHPUnit\Framework\MockObject\MockObject $permissionProvider */
        $permissionProvider = $this->createMock(ConfigurablePermissionProvider::class);
        $permissionProvider->expects($this->any())->method('get')
            ->willReturn($this->createMock(ConfigurablePermission::class));

        $this->filter = new AclPrivilegeConfigurableFilter($permissionProvider);
    }

    /**
     * @dataProvider filtersProvider
     *
     * @param array $filters
     * @param ArrayCollection $aclPrivileges
     * @param ArrayCollection $expected
     */
    public function testFilter(array $filters, ArrayCollection $aclPrivileges, ArrayCollection $expected)
    {
        foreach ($filters as $filter) {
            $this->filter->addConfigurableFilter($filter);
        }

        $this->assertEquals(
            $expected->toArray(),
            $this->filter->filter($aclPrivileges, 'default')->toArray()
        );
    }

    /**
     * @return \Generator
     */
    public function filtersProvider()
    {
        $emptyCollection = new ArrayCollection();
        $notEmptyCollection = new ArrayCollection([new AclPrivilege()]);

        yield 'without filters' => [
            'filters' => [],
            'aclPrivileges' => $notEmptyCollection,
            'expected' => $notEmptyCollection
        ];

        yield 'not supported filter' => [
            'filters' => [$this->createFilter(false, false)],
            'aclPrivileges' => $notEmptyCollection,
            'expected' => $notEmptyCollection
        ];

        yield 'supported filter' => [
            'filters' => [$this->createFilter(true, true)],
            'aclPrivileges' => $notEmptyCollection,
            'expected' => $notEmptyCollection
        ];

        yield 'supported filter and filter return false' => [
            'filters' => [$this->createFilter(true, false)],
            'aclPrivileges' => $notEmptyCollection,
            'expected' => $emptyCollection
        ];
    }

    /**
     * @param bool $isSupported
     * @param bool $result
     *
     * @return AclPrivilegeConfigurableFilterInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createFilter($isSupported, $result)
    {
        $filter = $this->createMock(AclPrivilegeConfigurableFilterInterface::class);
        $filter->expects($this->any())->method('isSupported')->willReturn($isSupported);
        $filter->expects($this->any())->method('filter')->willReturn($result);

        return $filter;
    }
}
