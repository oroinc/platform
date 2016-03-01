<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Group;

use Oro\Bundle\SecurityBundle\Acl\Group\AclGroupProviderInterface;
use Oro\Bundle\SecurityBundle\Acl\Group\ChainAclGroupProvider;

class ChainAclGroupProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructionWithoutProviders()
    {
        $chain = new ChainAclGroupProvider();

        $this->assertAttributeCount(0, 'providers', $chain);
    }

    public function testAddProvider()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|AclGroupProviderInterface $provider1 */
        $provider1 = $this->getMock('Oro\Bundle\SecurityBundle\Acl\Group\AclGroupProviderInterface');

        /** @var \PHPUnit_Framework_MockObject_MockObject|AclGroupProviderInterface $provider2 */
        $provider2 = $this->getMock('Oro\Bundle\SecurityBundle\Acl\Group\AclGroupProviderInterface');

        $chain = new ChainAclGroupProvider();
        $chain->addProvider('alias1', $provider1);

        $this->assertAttributeCount(1, 'providers', $chain);
        $this->assertAttributeContains($provider1, 'providers', $chain);

        $chain->addProvider('alias2', $provider2);

        $this->assertAttributeCount(2, 'providers', $chain);
        $this->assertAttributeContains($provider1, 'providers', $chain);
        $this->assertAttributeContains($provider2, 'providers', $chain);

        $chain->addProvider('alias2', $provider1);

        $this->assertAttributeCount(2, 'providers', $chain);
        $this->assertAttributeContains($provider1, 'providers', $chain);
        $this->assertAttributeNotContains($provider2, 'providers', $chain);
    }

    public function testSupports()
    {
        $chain = new ChainAclGroupProvider();
        $this->assertTrue($chain->supports());
    }

    public function testGetGroup()
    {
        $group1 = 'group1';
        $group2 = 'group2';

        $chain = new ChainAclGroupProvider();
        $chain->addProvider('alias1', $this->getAclGroupProviderMock(false, $group1));
        $chain->addProvider('alias2', $this->getAclGroupProviderMock(true, $group2));

        $result = $chain->getGroup();

        $this->assertInternalType('string', $result);
        $this->assertEquals($group2, $result);
    }

    /**
     * @param bool $isSupports
     * @param string $group
     * @return \PHPUnit_Framework_MockObject_MockObject|AclGroupProviderInterface
     */
    protected function getAclGroupProviderMock($isSupports = true, $group = '')
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|AclGroupProviderInterface $provider */
        $provider = $this->getMock('Oro\Bundle\SecurityBundle\Acl\Group\AclGroupProviderInterface');
        $provider->expects($this->any())
            ->method('supports')
            ->willReturn($isSupports);
        $provider->expects($isSupports ? $this->once() : $this->never())
            ->method('getGroup')
            ->willReturn($group);

        return $provider;
    }
}
