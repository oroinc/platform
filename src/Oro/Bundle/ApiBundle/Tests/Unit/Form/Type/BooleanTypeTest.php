<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Form;

use Symfony\Component\Form\Test\TypeTestCase;

use Oro\Bundle\ApiBundle\Form\Type\BooleanType;

class BooleanTypeTest extends TypeTestCase
{
    /**
     * @dataProvider validValuesDataProvider
     */
    public function testWithValidValue($value, $expected)
    {
        $form = $this->factory->create(new BooleanType());
        $form->submit($value);
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expected, $form->getData());
    }

    public function validValuesDataProvider()
    {
        return [
            ['true', true],
            ['yes', true],
            ['1', true],
            [true, true],
            [1, true],
            ['false', false],
            ['no', false],
            ['0', false],
            [false, false],
            [0, false],
            ['', null],
            [null, null],
        ];
    }

    public function testWithInvalidValue()
    {
        $form = $this->factory->create(new BooleanType());
        $form->submit('test');
        $this->assertFalse($form->isSynchronized());
    }

    public function testGetName()
    {
        $type = new BooleanType();
        $this->assertEquals('oro_api_boolean', $type->getName());
    }
}
