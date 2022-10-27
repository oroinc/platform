<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Twig;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\MigrationBundle\Twig\SchemaDumperExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class SchemaDumperExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var AbstractPlatform|\PHPUnit\Framework\MockObject\MockObject */
    private $platform;

    /** @var SchemaDumperExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->platform = $this->getMockBuilder(AbstractPlatform::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isCommentedDoctrineType'])
            ->getMockForAbstractClass();

        $container = self::getContainerBuilder()
            ->add(ManagerRegistry::class, $this->doctrine)
            ->getContainer($this);

        $this->extension = new SchemaDumperExtension($container);
    }

    public function testGetStringColumnOptions()
    {
        $this->platform->expects($this->once())
            ->method('isCommentedDoctrineType')
            ->willReturn(false);

        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())
            ->method('getDatabasePlatform')
            ->willReturn($this->platform);
        $this->doctrine->expects($this->once())
            ->method('getConnection')
            ->willReturn($connection);

        $column = new Column('string_column', Type::getType(Types::STRING));
        $column->setLength(255);
        $result = self::callTwigFunction($this->extension, 'oro_migration_get_schema_column_options', [$column]);
        $this->assertCount(1, $result);
        $this->assertEquals(255, $result['length']);
    }

    public function testGetIntegerColumnOptions()
    {
        $this->platform->expects($this->once())
            ->method('isCommentedDoctrineType')
            ->willReturn(true);

        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())
            ->method('getDatabasePlatform')
            ->willReturn($this->platform);
        $this->doctrine->expects($this->once())
            ->method('getConnection')
            ->willReturn($connection);

        $column = new Column('string_column', Type::getType(Types::INTEGER));
        $column->setNotnull(false);
        $column->setAutoincrement(true);
        $column->setUnsigned(true);
        $result = self::callTwigFunction($this->extension, 'oro_migration_get_schema_column_options', [$column]);
        $this->assertCount(4, $result);
        $this->assertTrue($result['unsigned']);
        $this->assertTrue($result['autoincrement']);
        $this->assertFalse($result['notnull']);
        $this->assertEquals('(DC2Type:integer)', $result['comment']);
    }
}
