<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Tools;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Tools\SchemaDumper;

class SchemaDumperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SchemaDumper
     */
    protected $schemaDumper;

    /**
     * @var Schema
     */
    protected $schema;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $twig;

    public function setUp()
    {
        $this->twig = $this->getMockBuilder('\Twig_Environment')->disableOriginalConstructor()->getMock();
        $this->schema = new Schema();
        $this->schemaDumper = new SchemaDumper($this->twig);

        $this->schemaDumper->acceptSchema($this->schema);
    }

    public function testDump()
    {
        $this->twig->expects($this->once())
            ->method('render')
            ->with(SchemaDumper::SCHEMA_TEMPLATE, ['schema' => $this->schema])
            ->will($this->returnValue(''));

        $this->schemaDumper->dump();
    }
}
