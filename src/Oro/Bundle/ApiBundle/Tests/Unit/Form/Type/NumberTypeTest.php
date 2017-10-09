<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\TypeTestCase;

use Oro\Bundle\ApiBundle\Form\Type\NumberType;

class NumberTypeTest extends TypeTestCase
{
    /**
     * @dataProvider validValuesDataProvider
     */
    public function testWithValidValue($scale, $value, $expected)
    {
        $form = $this->factory->create(new NumberType(), null, ['scale' => $scale]);
        $form->submit($value);
        $this->assertTrue($form->isSynchronized());
        $this->assertSame($expected, $form->getData());
    }

    public function validValuesDataProvider()
    {
        return [
            [null, '1.23456789', '1.23456789'],
            [0, '123456789', '123456789'],
            [3, '1.23456789', '1.235'],
            [3, '1.234', '1.234'],
        ];
    }

    /**
     * @dataProvider invalidValuesDataProvider
     */
    public function testWithInvalidValue($scale, $value)
    {
        $form = $this->factory->create(new NumberType(), null, ['scale' => $scale]);
        $form->submit($value);
        $this->assertFalse($form->isSynchronized());
    }

    public function invalidValuesDataProvider()
    {
        return [
            [null, 'test'],
            [0, 'test'],
            [3, 'test'],
            [0, '1.2'],
        ];
    }

    public function testGetName()
    {
        $type = new NumberType();
        $this->assertEquals('oro_api_number', $type->getName());
    }
}
