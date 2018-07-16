<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Migration;

use Oro\Bundle\SecurityBundle\Migration\DeleteAclMigrationQuery;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;

class DeleteAclMigrationQueryTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $container;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $aclProvider;

    /** @var ObjectIdentityInterface */
    protected $oid;

    protected function setUp()
    {
        $this->container   = $this->createMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $this->oid         = new ObjectIdentity('entity', 'Test\Class');
        $this->aclProvider = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Acl\Dbal\MutableAclProvider')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testGetDescription()
    {
        $this->container->expects($this->once())
            ->method('get')
            ->with('oro_security.alias.acl.dbal.provider', ContainerInterface::NULL_ON_INVALID_REFERENCE)
            ->will($this->returnValue($this->aclProvider));

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
            ->will($this->returnValue(null));

        $query = new DeleteAclMigrationQuery($this->container, $this->oid);

        $this->assertNull($query->getDescription());
    }

    public function testExecute()
    {
        $this->container->expects($this->once())
            ->method('get')
            ->with('oro_security.alias.acl.dbal.provider', ContainerInterface::NULL_ON_INVALID_REFERENCE)
            ->will($this->returnValue($this->aclProvider));

        $query = new DeleteAclMigrationQuery($this->container, $this->oid);

        $logger = $this->createMock('Psr\Log\LoggerInterface');
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
            ->will($this->returnValue(null));

        $query = new DeleteAclMigrationQuery($this->container, $this->oid);

        $logger = $this->createMock('Psr\Log\LoggerInterface');
        $logger->expects($this->never())
            ->method('info');
        $this->aclProvider->expects($this->never())
            ->method('deleteAclClass');

        $query->execute($logger);
    }
}
