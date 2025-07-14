<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Migration;

use Oro\Bundle\SecurityBundle\Acl\Dbal\MutableAclProvider;
use Oro\Bundle\SecurityBundle\Migration\DeleteAclMigrationQuery;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;

class DeleteAclMigrationQueryTest extends TestCase
{
    private ContainerInterface&MockObject $container;
    private MutableAclProvider&MockObject $aclProvider;
    private ObjectIdentityInterface $oid;

    #[\Override]
    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->aclProvider = $this->createMock(MutableAclProvider::class);
        $this->oid = new ObjectIdentity('entity', 'Test\Class');
    }

    public function testGetDescription(): void
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

    public function testGetDescriptionNoAclProvider(): void
    {
        $this->container->expects($this->once())
            ->method('get')
            ->with('oro_security.alias.acl.dbal.provider', ContainerInterface::NULL_ON_INVALID_REFERENCE)
            ->willReturn(null);

        $query = new DeleteAclMigrationQuery($this->container, $this->oid);

        $this->assertNull($query->getDescription());
    }

    public function testExecute(): void
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

    public function testExecuteNoAclProvider(): void
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
