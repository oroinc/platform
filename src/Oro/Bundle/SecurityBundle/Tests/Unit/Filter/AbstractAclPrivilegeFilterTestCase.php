<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Filter;

use Oro\Bundle\SecurityBundle\Filter\AclPrivilegeConfigurableFilterInterface;
use Oro\Bundle\SecurityBundle\Model\AclPrivilege;

abstract class AbstractAclPrivilegeFilterTestCase extends \PHPUnit\Framework\TestCase
{
    /** @var AclPrivilegeConfigurableFilterInterface */
    protected $filter;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->filter = $this->createFilter();
    }

    /**
     * @dataProvider isSupportedAclPrivilegeProvider
     *
     * @param AclPrivilege $aclPrivilege
     * @param bool $isSupported
     */
    public function testIsSupported(AclPrivilege $aclPrivilege, $isSupported)
    {
        $this->assertSame($isSupported, $this->filter->isSupported($aclPrivilege));
    }

    /**
     * @return array
     */
    abstract public function isSupportedAclPrivilegeProvider();

    /**
     * @return AclPrivilegeConfigurableFilterInterface
     */
    abstract protected function createFilter();
}
