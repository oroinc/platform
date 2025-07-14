<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Group;

use Oro\Bundle\SecurityBundle\Acl\Group\AclGroupProviderInterface;
use Oro\Bundle\SecurityBundle\Acl\Group\ChainAclGroupProvider;
use PHPUnit\Framework\TestCase;

class ChainAclGroupProviderTest extends TestCase
{
    public function testSupports(): void
    {
        $chain = new ChainAclGroupProvider([]);
        $this->assertTrue($chain->supports());
    }

    public function testGetGroup(): void
    {
        $group1 = 'group1';
        $group2 = 'group2';

        $chain = new ChainAclGroupProvider([
            $this->getAclGroupProvider(false, $group1),
            $this->getAclGroupProvider(true, $group2)
        ]);

        $this->assertSame($group2, $chain->getGroup());
    }

    public function testGetGroupForDefaultGroup(): void
    {
        $chain = new ChainAclGroupProvider([
            $this->getAclGroupProvider(false, 'group1'),
            $this->getAclGroupProvider(false, 'group2')
        ]);

        $this->assertSame(AclGroupProviderInterface::DEFAULT_SECURITY_GROUP, $chain->getGroup());
    }

    private function getAclGroupProvider(bool $isSupports, string $group): AclGroupProviderInterface
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
