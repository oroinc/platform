<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Model;

use Oro\Bundle\EntityBundle\Model\EntityFieldStructure;
use Oro\Bundle\EntityBundle\Model\EntityStructure;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class EntityStructureTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        static::assertPropertyAccessors(new EntityStructure(), [
            ['label', 'label'],
            ['pluralLabel', 'pluralLabel'],
            ['alias', 'alias'],
            ['pluralAlias', 'pluralAlias'],
            ['className', 'className'],
            ['icon', 'icon'],
            ['fields', [(new EntityFieldStructure())->setName('field1')]],
            ['options', ['option1' => true]],
            ['routes', ['view' => 'route']],
        ]);
    }

    /**
     * @expectedException \Oro\Bundle\EntityBundle\Exception\OptionNotFoundException
     * @expectedExceptionMessage Option "unknown" not found
     */
    public function testGetOptionThrowsException()
    {
        $item = new EntityStructure();
        $this->assertFalse($item->hasOption('unknown'));
        $item->getOption('unknown');
    }

    /**
     * @param mixed $expected
     * @param mixed $className
     *
     * @dataProvider getIdDataProvider
     */
    public function testGetId($expected, $className)
    {
        $data = (new EntityStructure())->setClassName($className);
        $this->assertSame($expected, $data->getId());
    }

    /**
     * @return array
     */
    public function getIdDataProvider()
    {
        return [
            'null' => ['expected' => '', 'className' => null],
            'empty' => ['expected' => '', 'className' => ''],
            'simple' => ['expected' => \stdClass::class, 'className' => \stdClass::class],
            'with namespace' => [
                'expected' => 'Oro_Bundle_EntityBundle_Tests_Unit_Model_EntityStructureTest',
                'className' => __CLASS__
            ],
        ];
    }
}
