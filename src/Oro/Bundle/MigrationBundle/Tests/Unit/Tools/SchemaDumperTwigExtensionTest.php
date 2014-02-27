<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Tools;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Type;
use Oro\Bundle\MigrationBundle\Tools\SchemaDumperTwigExtension;

class SchemaDumperTwigExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SchemaDumperTwigExtension
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
        $this->extension = new SchemaDumperTwigExtension();
        $this->extension->setPlatform($this->platform);
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
