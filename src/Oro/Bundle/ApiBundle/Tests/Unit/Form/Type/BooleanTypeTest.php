<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ApiBundle\Form\Type\BooleanType;
use Symfony\Component\Form\Test\TypeTestCase;

class BooleanTypeTest extends TypeTestCase
{
    /**
     * @dataProvider validValuesDataProvider
     */
    public function testWithValidValue($value, $expected)
    {
        $form = $this->factory->create(BooleanType::class);
        $form->submit($value);
        self::assertTrue($form->isSynchronized());
        self::assertSame($expected, $form->getData());
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
            [false, null], // Symfony Form treats false as NULL due to checkboxes
            [0, false],
            ['', null],
            [null, null]
        ];
    }

    /**
     * @dataProvider invalidValuesDataProvider
     */
    public function testWithInvalidValue($value)
    {
        $form = $this->factory->create(BooleanType::class);
        $form->submit($value);
        self::assertFalse($form->isSynchronized());
    }

    public function invalidValuesDataProvider()
    {
        return [
            ['test']
        ];
    }
}
