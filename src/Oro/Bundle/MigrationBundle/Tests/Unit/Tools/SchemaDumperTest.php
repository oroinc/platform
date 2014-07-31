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

    protected function setUp()
    {
        $this->twig = $this->getMockBuilder('\Twig_Environment')->disableOriginalConstructor()->getMock();
        $this->schema = new Schema();
        $this->schemaDumper = new SchemaDumper($this->twig);

        $this->schemaDumper->acceptSchema($this->schema);
    }

    /**
     * @dataProvider dumpDataProvider
     * @param string|null $namespace
     * @param array|null $allowedTables
     * @param string|null $expectedNamespace
     */
    public function testDump($allowedTables, $namespace, $expectedNamespace)
    {
        $this->twig->expects($this->once())
            ->method('render')
            ->with(
                SchemaDumper::SCHEMA_TEMPLATE,
                [
                    'schema' => $this->schema,
                    'allowedTables' => $allowedTables,
                    'namespace' => $expectedNamespace
                ]
            )
            ->will($this->returnValue('TEST'));

        $this->assertEquals('TEST', $this->schemaDumper->dump($allowedTables, $namespace));
    }

    public function dumpDataProvider()
    {
        return array(
            array(null, null, null),
            array(array('test' => true), 'Acme\DemoBundle\Entity', 'Acme\DemoBundle')
        );
    }
}
