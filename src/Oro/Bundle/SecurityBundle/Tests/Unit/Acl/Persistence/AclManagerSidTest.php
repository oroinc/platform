<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Persistence;

use Oro\Bundle\SecurityBundle\Acl\Dbal\MutableAclProvider;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclSidManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;

class AclManagerSidTest extends TestCase
{
    private MutableAclProvider&MockObject $aclProvider;
    private AclSidManager $manager;

    #[\Override]
    protected function setUp(): void
    {
        $this->aclProvider = $this->createMock(MutableAclProvider::class);

        $this->manager = new AclSidManager($this->aclProvider);
    }

    public function testIsAclEnabled(): void
    {
        $manager = new AclSidManager();
        $this->assertFalse($manager->isAclEnabled());
        $aclProvider = $this->createMock(MutableAclProvider::class);
        $manager = new AclSidManager($aclProvider);

        $this->assertTrue($manager->isAclEnabled());
    }

    public function testUpdateSid(): void
    {
        $sid = $this->createMock(SecurityIdentityInterface::class);
        $this->aclProvider->expects($this->once())
            ->method('updateSecurityIdentity')
            ->with($this->identicalTo($sid), $this->equalTo('old'));

        $this->manager->updateSid($sid, 'old');
    }

    public function testDeleteSid(): void
    {
        $sid = $this->createMock(SecurityIdentityInterface::class);
        $this->aclProvider->expects($this->once())
            ->method('deleteSecurityIdentity')
            ->with($this->identicalTo($sid));

        $this->manager->deleteSid($sid);
    }
}
