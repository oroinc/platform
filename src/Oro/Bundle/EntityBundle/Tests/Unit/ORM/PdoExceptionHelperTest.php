<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\ORM;

use Doctrine\Common\Persistence\ManagerRegistry;

use Doctrine\DBAL\Driver\PDOException;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Oro\Bundle\EntityBundle\ORM\PDOExceptionHelper;

class PDOExceptionHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var PDOExceptionHelper
     */
    protected $pdoExceptionHelper;

    protected function setUp()
    {
        $this->registry = $this->getMock(ManagerRegistry::class);
        $this->pdoExceptionHelper = new PDOExceptionHelper($this->registry);
    }

    public function testIsDeadlockMySQL()
    {
        $exception = new PDOException(new \PDOException('Exception message', 1213));

        $connection = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();

        $connection->expects($this->once())
            ->method('getDatabasePlatform')
            ->willReturn(new MySqlPlatform());

        $this->registry->expects($this->once())
            ->method('getConnection')
            ->willReturn($connection);

        $this->assertTrue($this->pdoExceptionHelper->isDeadlock($exception));
    }

    public function testIsDeadlockPostgreSQL()
    {
        $pdoException = new \PDOException();
        $pdoException->errorInfo = [1 => '40P01'];
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

        $this->assertTrue($this->pdoExceptionHelper->isDeadlock($exception));
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

        $this->assertFalse($this->pdoExceptionHelper->isDeadlock($exception));
    }
}
