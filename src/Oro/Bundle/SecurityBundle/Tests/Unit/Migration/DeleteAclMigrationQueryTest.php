<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Migration;

use Oro\Bundle\SecurityBundle\Migration\DeleteAclMigrationQuery;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;

class DeleteAclMigrationQueryTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $container;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $aclProvider;

    /** @var ObjectIdentityInterface */
    protected $oid;

    protected function setUp()
    {
        $this->container   = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $this->oid         = new ObjectIdentity('entity', 'Test\Class');
        $this->aclProvider = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Acl\Dbal\MutableAclProvider')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testGetDescription()
    {
        $this->container->expects($this->once())
            ->method('get')
            ->with('security.acl.dbal.provider', ContainerInterface::NULL_ON_INVALID_REFERENCE)
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
            ->with('security.acl.dbal.provider', ContainerInterface::NULL_ON_INVALID_REFERENCE)
            ->will($this->returnValue(null));

        $query = new DeleteAclMigrationQuery($this->container, $this->oid);

        $this->assertNull($query->getDescription());
    }

    public function testExecute()
    {
        $this->container->expects($this->once())
            ->method('get')
            ->with('security.acl.dbal.provider', ContainerInterface::NULL_ON_INVALID_REFERENCE)
            ->will($this->returnValue($this->aclProvider));

        $query = new DeleteAclMigrationQuery($this->container, $this->oid);

        $logger = $this->getMock('Psr\Log\LoggerInterface');
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
            ->with('security.acl.dbal.provider', ContainerInterface::NULL_ON_INVALID_REFERENCE)
            ->will($this->returnValue(null));

        $query = new DeleteAclMigrationQuery($this->container, $this->oid);

        $logger = $this->getMock('Psr\Log\LoggerInterface');
        $logger->expects($this->never())
            ->method('info');
        $this->aclProvider->expects($this->never())
            ->method('deleteAclClass');

        $query->execute($logger);
    }
}
