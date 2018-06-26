<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\ORM;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Driver\AbstractDriverException;
use Doctrine\DBAL\Driver\PDOException;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Oro\Bundle\EntityBundle\ORM\DatabaseExceptionHelper;

class DatabaseExceptionHelperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $registry;

    /**
     * @var DatabaseExceptionHelper
     */
    protected $databaseExceptionHelper;

    protected function setUp()
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->databaseExceptionHelper = new DatabaseExceptionHelper($this->registry);
    }

    /**
     * @dataProvider deadlockDataProvider
     * @param string $sqlState
     * @param string $code
     */
    public function testIsDeadlockMySQL($sqlState, $code)
    {
        $pdoException = new PDOException(new \PDOException('Exception message', $code));
        $pdoException->errorInfo = [0 => $sqlState, 1 => $code];

        $connection = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();

        $connection->expects($this->once())
            ->method('getDatabasePlatform')
            ->willReturn(new MySqlPlatform());

        $this->registry->expects($this->once())
            ->method('getConnection')
            ->willReturn($connection);

        $this->assertTrue($this->databaseExceptionHelper->isDeadlock($pdoException));
    }

    /**
     * @return array
     */
    public function deadlockDataProvider()
    {
        return [
            'deadlock' => ['40001', '1213'],
            'lock timeout' => ['HY000', '1205']
        ];
    }

    public function testIsDeadlockPostgreSQL()
    {
        $pdoException = new \PDOException();
        $pdoException->errorInfo = [0 => '40P01'];
        $exception = new PDOException($pdoException);

        $connection = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();

        $connection->expects($this->once())
            ->method('getDatabasePlatform')
            ->willReturn(new PostgreSqlPlatform());

        $this->registry->expects($this->once())
            ->method('getConnection')
            ->willReturn($connection);

        $this->assertTrue($this->databaseExceptionHelper->isDeadlock($exception));
    }

    public function testNotDeadlock()
    {
        $exception = new PDOException(new \PDOException('Exception message', 1234));

        $connection = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();

        $connection->expects($this->once())
            ->method('getDatabasePlatform')
            ->willReturn(new MySqlPlatform());

        $this->registry->expects($this->once())
            ->method('getConnection')
            ->willReturn($connection);

        $this->assertFalse($this->databaseExceptionHelper->isDeadlock($exception));
    }

    public function testGetDriverExceptionNotDriverException()
    {
        $exception = new \Exception();
        $this->assertNull($this->databaseExceptionHelper->getDriverException($exception));
    }

    public function testGetDriverException()
    {
        $exception = $this->createMock(AbstractDriverException::class);
        $this->assertSame($exception, $this->databaseExceptionHelper->getDriverException($exception));
    }

    public function testGetDriverExceptionFromPdoException()
    {
        $driverException = $this->createMock(AbstractDriverException::class);
        $exception = new \Doctrine\DBAL\Exception\DriverException('Exception', $driverException);
        $this->assertSame($driverException, $this->databaseExceptionHelper->getDriverException($exception));
    }
}
