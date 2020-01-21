<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Group;

use Oro\Bundle\SecurityBundle\Acl\Group\AclGroupProviderInterface;
use Oro\Bundle\SecurityBundle\Acl\Group\ChainAclGroupProvider;

class ChainAclGroupProviderTest extends \PHPUnit\Framework\TestCase
{
    public function testSupports()
    {
        $chain = new ChainAclGroupProvider([]);
        $this->assertTrue($chain->supports());
    }

    public function testGetGroup()
    {
        $group1 = 'group1';
        $group2 = 'group2';

        $chain = new ChainAclGroupProvider([
            $this->getAclGroupProviderMock(false, $group1),
            $this->getAclGroupProviderMock(true, $group2)
        ]);

        $this->assertSame($group2, $chain->getGroup());
    }

    public function testGetGroupForDefaultGroup()
    {
        $chain = new ChainAclGroupProvider([
            $this->getAclGroupProviderMock(false, 'group1'),
            $this->getAclGroupProviderMock(false, 'group2')
        ]);

        $this->assertSame(AclGroupProviderInterface::DEFAULT_SECURITY_GROUP, $chain->getGroup());
    }

    /**
     * @param bool   $isSupports
     * @param string $group
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|AclGroupProviderInterface
     */
    private function getAclGroupProviderMock(bool $isSupports, string $group)
    {
        $provider = $this->createMock(AclGroupProviderInterface::class);
        $provider->expects($this->any())
            ->method('supports')
            ->willReturn($isSupports);
        $provider->expects($isSupports ? $this->once() : $this->never())
            ->method('getGroup')
            ->willReturn($group);

        return $provider;
    }
}
