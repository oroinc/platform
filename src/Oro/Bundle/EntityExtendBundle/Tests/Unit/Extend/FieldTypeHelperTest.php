<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Extend;

use Oro\Bundle\EntityExtendBundle\Extend\FieldTypeHelper;

class FieldTypeHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FieldTypeHelper
     */
    private $helper;

    protected function setUp()
    {
        $this->helper = new FieldTypeHelper(['enum' => 'manyToOne']);
    }

    /**
     * @dataProvider isRealRelationProvider
     */
    public function testIsRealRelation($type, $expectedResult)
    {
        $this->assertEquals(
            $expectedResult,
            $this->helper->isRealRelation($type)
        );
    }

    public function isRealRelationProvider()
    {
        return [
            ['ref-one', true],
            ['ref-many', true],
            ['manyToOne', true],
            ['oneToMany', true],
            ['manyToMany', true],
            ['integer', false],
            ['enum', false]
        ];
    }

    /**
     * @dataProvider getUnderlyingTypeProvider
     */
    public function testGetUnderlyingType($type, $expectedType)
    {
        $this->assertEquals(
            $expectedType,
            $this->helper->getUnderlyingType($type)
        );
    }

    public function getUnderlyingTypeProvider()
    {
        return [
            ['ref-one', 'ref-one'],
            ['ref-many', 'ref-many'],
            ['manyToOne', 'manyToOne'],
            ['oneToMany', 'oneToMany'],
            ['manyToMany', 'manyToMany'],
            ['integer', 'integer'],
            ['enum', 'manyToOne']
        ];
    }
}
