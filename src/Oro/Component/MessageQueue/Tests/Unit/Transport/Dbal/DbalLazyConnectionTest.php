<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Transport\Dbal;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Oro\Component\MessageQueue\Transport\Dbal\DbalConnection;
use Oro\Component\MessageQueue\Transport\Dbal\DbalLazyConnection;
use Oro\Component\Testing\ClassExtensionTrait;

class DbalLazyConnectionTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementConnectionInterface()
    {
        self::assertClassExtends(DbalConnection::class, DbalLazyConnection::class);
    }
    
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new DbalLazyConnection($this->createManagerRegistryStub(), 'connection', 'table');
    }

    public function testShouldNotBeInitOnConstruct()
    {
        $connection = new DbalLazyConnection($this->createManagerRegistryStub(), 'connection', 'table');

        self::assertConnectionIsNotInit($connection);
    }

    public function testShouldInitDBALConnectionUsingRegistryAndConnectionName()
    {
        $dbalConnection = $this->createDBALConnectionMock();

        $registry = $this->createManagerRegistryMock();
        $registry
            ->expects(self::once())
            ->method('getConnection')
            ->with('theConnection')
            ->willReturn($dbalConnection)
        ;

        $connection = new DbalLazyConnection($registry, 'theConnection', 'table');

        //guard
        self::assertConnectionIsNotInit($connection);

        self::assertSame($dbalConnection, $connection->getDBALConnection());
        self::assertConnectionIsInit($connection);
    }

    public function testShouldNotInitOnCreateSessionMethodCall()
    {
        $connection = new DbalLazyConnection($this->createManagerRegistryStub(), 'connection', 'table');

        //guard
        self::assertConnectionIsNotInit($connection);

        $session = $connection->createSession();

        self::assertAttributeInstanceOf(DbalLazyConnection::class, 'connection', $session);
        self::assertConnectionIsNotInit($connection);
    }

    public function testShouldNotInitOnGetTableNameCall()
    {
        $connection = new DbalLazyConnection($this->createManagerRegistryStub(), 'connection', 'theTable');

        //guard
        self::assertConnectionIsNotInit($connection);

        self::assertEquals('theTable', $connection->getTableName());

        self::assertConnectionIsNotInit($connection);
    }

    public function testShouldNotInitOnGetOptionsCall()
    {
        $options = ['foo' => 'fooVal', 'bar' => 'barVal'];

        $connection = new DbalLazyConnection($this->createManagerRegistryStub(), 'connection', 'table', $options);

        //guard
        self::assertConnectionIsNotInit($connection);

        self::assertEquals($options, $connection->getOptions());

        self::assertConnectionIsNotInit($connection);
    }

    public function testShouldNotInitOnCloseIfNotInit()
    {
        $dbalConnection = $this->createDBALConnectionMock();
        $dbalConnection
            ->expects(self::never())
            ->method('close')
        ;

        $registry = $this->createManagerRegistryStub($dbalConnection);

        $connection = new DbalLazyConnection($registry, 'connection', 'table');

        //guard
        self::assertConnectionIsNotInit($connection);

        $connection->close();

        self::assertConnectionIsNotInit($connection);
    }

    public function testShouldCallCloseInitOnCloseIfNotInit()
    {
        $dbalConnection = $this->createDBALConnectionMock();
        $dbalConnection
            ->expects(self::once())
            ->method('close')
        ;

        $registry = $this->createManagerRegistryStub($dbalConnection);

        $connection = new DbalLazyConnection($registry, 'connection', 'table');

        $connection->getDBALConnection();

        //guard
        self::assertConnectionIsInit($connection);

        $connection->close();
    }

    private static function assertConnectionIsNotInit(DbalLazyConnection $connection)
    {
        self::assertAttributeSame(false, 'isInit', $connection);
    }

    private static function assertConnectionIsInit(DbalLazyConnection $connection)
    {
        self::assertAttributeSame(true, 'isInit', $connection);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry
     */
    private function createManagerRegistryMock()
    {
        return $this->getMock(ManagerRegistry::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry
     */
    private function createManagerRegistryStub($connection = null)
    {
        $registryMock = $this->createManagerRegistryMock();
        $registryMock
            ->expects(self::any())
            ->method('getConnection')
            ->willReturn($connection)
        ;

        return $registryMock;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Connection
     */
    private function createDBALConnectionMock()
    {
        $schemaManager = $this->getMock(AbstractSchemaManager::class, [], [], '', false);

        $dbalConnection = $this->getMock(Connection::class, [], [], '', false);
        $dbalConnection
            ->expects($this->any())
            ->method('getSchemaManager')
            ->will($this->returnValue($schemaManager))
        ;

        return $dbalConnection;
    }
}
