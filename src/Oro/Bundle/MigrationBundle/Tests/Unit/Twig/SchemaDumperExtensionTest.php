<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Tools;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Type;

use Oro\Bundle\MigrationBundle\Twig\SchemaDumperExtension;

class SchemaDumperExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SchemaDumperExtension
     */
    protected $extension;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $platform;

    public function setUp()
    {
        $this->platform = $this->getMockBuilder('Doctrine\DBAL\Platforms\AbstractPlatform')
            ->disableOriginalConstructor()
            ->setMethods(['isCommentedDoctrineType'])
            ->getMockForAbstractClass();

        $connection = $this->getMockBuilder('Doctrine\DBAL\Connection')->disableOriginalConstructor()->getMock();
        $connection->expects($this->once())
            ->method('getDatabasePlatform')
            ->will($this->returnValue($this->platform));

        $managerRegistry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $managerRegistry->expects($this->once())
            ->method('getConnection')
            ->will($this->returnValue($connection));

        $this->extension = new SchemaDumperExtension($managerRegistry);
    }

    public function testGetName()
    {
        $this->assertEquals('schema_dumper_extension', $this->extension->getName());
    }

    public function testGetFunctions()
    {
        $this->assertEquals(1, count($this->extension->getFunctions()));
    }

    public function testGetStringColumnOptions()
    {
        $this->platform->expects($this->once())
            ->method('isCommentedDoctrineType')
            ->will($this->returnValue(false));

        $column = new Column('string_column', Type::getType(Type::STRING));
        $column->setLength(255);
        $result = $this->extension->getColumnOptions($column);
        $this->assertEquals(1, count($result));
        $this->assertEquals(255, $result['length']);
    }

    public function testGetIntegerColumnOptions()
    {
        $this->platform->expects($this->once())
            ->method('isCommentedDoctrineType')
            ->will($this->returnValue(true));

        $column = new Column('string_column', Type::getType(Type::INTEGER));
        $column->setNotnull(false);
        $column->setAutoincrement(true);
        $column->setUnsigned(true);
        $result = $this->extension->getColumnOptions($column);
        $this->assertEquals(4, count($result));
        $this->assertTrue($result['unsigned']);
        $this->assertTrue($result['autoincrement']);
        $this->assertFalse($result['notnull']);
        $this->assertEquals('(DC2Type:integer)', $result['comment']);
    }
}
