<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Persistence;

use Oro\Bundle\SecurityBundle\Acl\Dbal\MutableAclProvider;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclSidManager;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;

class AclManagerSidTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $aclProvider;

    /** @var AclSidManager */
    private $manager;

    protected function setUp(): void
    {
        $this->aclProvider = $this->createMock(MutableAclProvider::class);

        $this->manager = new AclSidManager(
            $this->aclProvider
        );
    }

    public function testIsAclEnabled()
    {
        $manager = new AclSidManager();
        $this->assertFalse($manager->isAclEnabled());
        $aclProvider = $this->createMock(MutableAclProvider::class);
        $manager = new AclSidManager($aclProvider);

        $this->assertTrue($manager->isAclEnabled());
    }

    public function testUpdateSid()
    {
        $sid = $this->createMock(SecurityIdentityInterface::class);
        $this->aclProvider->expects($this->once())
            ->method('updateSecurityIdentity')
            ->with($this->identicalTo($sid), $this->equalTo('old'));

        $this->manager->updateSid($sid, 'old');
    }

    public function testDeleteSid()
    {
        $sid = $this->createMock(SecurityIdentityInterface::class);
        $this->aclProvider->expects($this->once())
            ->method('deleteSecurityIdentity')
            ->with($this->identicalTo($sid));

        $this->manager->deleteSid($sid);
    }
}
