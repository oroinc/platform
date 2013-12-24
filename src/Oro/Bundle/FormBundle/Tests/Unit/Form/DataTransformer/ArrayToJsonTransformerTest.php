<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\DataTransformer\ArrayToJsonTransformer;

class ArrayToJsonTransformerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider transformDataProvider
     * @param mixed $value
     * @param mixed $expectedValue
     */
    public function testTransform($value, $expectedValue)
    {
        $transformer = $this->createTestTransfomer();
        $this->assertEquals($expectedValue, $transformer->transform($value));
    }

    public function transformDataProvider()
    {
        return array(
            'default' => array(
                array(1, 2, 3, 4),
                json_encode(array(1, 2, 3, 4)),
            ),
            'null' => array(
                null,
                ''
            ),
        );
    }

    /**
     * @expectedException \Symfony\Component\Form\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Expected argument of type "array", "string" given
     */
    public function testTransformFailsWhenUnexpectedType()
    {
        $transformer = $this->createTestTransfomer();
        $transformer->transform('');
    }

    /**
     * @dataProvider reverseTransformDataProvider
     * @param mixed $value
     * @param mixed $expectedValue
     */
    public function testReverseTransform($value, $expectedValue)
    {
        $transformer = $this->createTestTransfomer();
        $this->assertEquals($expectedValue, $transformer->reverseTransform($value));
    }

    public function reverseTransformDataProvider()
    {
        return array(
            'default' => array(
                '[1,2,3,4]',
                array('1', '2', '3', '4')
            ),
            'null' => array(
                json_encode(null),
                ''
            ),
            'empty' => array(
                null,
                []
            ),
        );
    }

    /**
     * @expectedException \Symfony\Component\Form\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Expected argument of type "string", "array" given
     */
    public function testReverseTransformFailsWhenUnexpectedType()
    {
        $this->createTestTransfomer()->reverseTransform(array());
    }

    /**
     * @return ArrayToJsonTransformer
     */
    private function createTestTransfomer()
    {
        return new ArrayToJsonTransformer();
    }
}
