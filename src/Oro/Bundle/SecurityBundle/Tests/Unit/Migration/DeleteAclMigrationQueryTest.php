<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Migration;

use Oro\Bundle\SecurityBundle\Acl\Dbal\MutableAclProvider;
use Oro\Bundle\SecurityBundle\Migration\DeleteAclMigrationQuery;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;

class DeleteAclMigrationQueryTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContainerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $container;

    /** @var MutableAclProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $aclProvider;

    /** @var ObjectIdentityInterface */
    private $oid;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->aclProvider = $this->createMock(MutableAclProvider::class);
        $this->oid = new ObjectIdentity('entity', 'Test\Class');
    }

    public function testGetDescription()
    {
        $this->container->expects($this->once())
            ->method('get')
            ->with('oro_security.alias.acl.dbal.provider', ContainerInterface::NULL_ON_INVALID_REFERENCE)
            ->willReturn($this->aclProvider);

        $query = new DeleteAclMigrationQuery($this->container, $this->oid);

        $this->assertEquals(
            sprintf('Remove ACL for %s.', (string)$this->oid),
            $query->getDescription()
        );
    }

    public function testGetDescriptionNoAclProvider()
    {
        $this->container->expects($this->once())
            ->method('get')
            ->with('oro_security.alias.acl.dbal.provider', ContainerInterface::NULL_ON_INVALID_REFERENCE)
            ->willReturn(null);

        $query = new DeleteAclMigrationQuery($this->container, $this->oid);

        $this->assertNull($query->getDescription());
    }

    public function testExecute()
    {
        $this->container->expects($this->once())
            ->method('get')
            ->with('oro_security.alias.acl.dbal.provider', ContainerInterface::NULL_ON_INVALID_REFERENCE)
            ->willReturn($this->aclProvider);

        $query = new DeleteAclMigrationQuery($this->container, $this->oid);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('info')
            ->with(sprintf('Remove ACL for %s.', (string)$this->oid));
        $this->aclProvider->expects($this->once())
            ->method('deleteAclClass')
            ->with($this->identicalTo($this->oid));

        $query->execute($logger);
    }

    public function testExecuteNoAclProvider()
    {
        $this->container->expects($this->once())
            ->method('get')
            ->with('oro_security.alias.acl.dbal.provider', ContainerInterface::NULL_ON_INVALID_REFERENCE)
            ->willReturn(null);

        $query = new DeleteAclMigrationQuery($this->container, $this->oid);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())
            ->method('info');
        $this->aclProvider->expects($this->never())
            ->method('deleteAclClass');

        $query->execute($logger);
    }
}
