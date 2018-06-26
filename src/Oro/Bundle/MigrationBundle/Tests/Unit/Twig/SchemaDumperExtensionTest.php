<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Tools;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Type;
use Oro\Bundle\MigrationBundle\Twig\SchemaDumperExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class SchemaDumperExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var SchemaDumperExtension */
    protected $extension;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $platform;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $doctrine;

    protected function setUp()
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->extension = new SchemaDumperExtension($this->doctrine);
    }

    public function testGetName()
    {
        $this->assertEquals('schema_dumper_extension', $this->extension->getName());
    }

    public function testGetStringColumnOptions()
    {
        $this->assertPlatform();
        $this->platform->expects($this->once())
            ->method('isCommentedDoctrineType')
            ->will($this->returnValue(false));

        $column = new Column('string_column', Type::getType(Type::STRING));
        $column->setLength(255);
        $result = $this->extension->getColumnOptions($column);
        $this->assertCount(1, $result);
        $this->assertEquals(255, $result['length']);
    }

    public function testGetIntegerColumnOptions()
    {
        $this->assertPlatform();
        $this->platform->expects($this->once())
            ->method('isCommentedDoctrineType')
            ->will($this->returnValue(true));

        $column = new Column('string_column', Type::getType(Type::INTEGER));
        $column->setNotnull(false);
        $column->setAutoincrement(true);
        $column->setUnsigned(true);
        $result = $this->extension->getColumnOptions($column);
        $this->assertCount(4, $result);
        $this->assertTrue($result['unsigned']);
        $this->assertTrue($result['autoincrement']);
        $this->assertFalse($result['notnull']);
        $this->assertEquals('(DC2Type:integer)', $result['comment']);
    }

    protected function assertPlatform()
    {
        $this->platform = $this->getMockBuilder('Doctrine\DBAL\Platforms\AbstractPlatform')
            ->disableOriginalConstructor()
            ->setMethods(['isCommentedDoctrineType'])
            ->getMockForAbstractClass();

        $connection = $this->getMockBuilder('Doctrine\DBAL\Connection')->disableOriginalConstructor()->getMock();
        $connection->expects($this->once())
            ->method('getDatabasePlatform')
            ->will($this->returnValue($this->platform));
        $this->doctrine->expects($this->once())
            ->method('getConnection')
            ->will($this->returnValue($connection));
    }
}
