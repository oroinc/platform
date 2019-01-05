<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ApiBundle\Form\Type\ArrayType;
use Symfony\Component\Form\Test\TypeTestCase;

class ArrayTypeTest extends TypeTestCase
{
    /**
     * @dataProvider validValuesDataProvider
     */
    public function testWithValidValue($value, $expected)
    {
        $form = $this->factory->create(ArrayType::class);
        $form->submit($value);
        self::assertTrue($form->isSynchronized());
        self::assertSame($expected, $form->getData());
    }

    public function validValuesDataProvider()
    {
        return [
            [[], []],
            [[1, 2], [1, 2]],
            [['key' => 'value'], ['key' => 'value']],
            ['', null],
            [null, null]
        ];
    }

    /**
     * @dataProvider invalidValuesDataProvider
     */
    public function testWithInvalidValue($value)
    {
        $form = $this->factory->create(ArrayType::class);
        $form->submit($value);
        self::assertFalse($form->isSynchronized());
    }

    public function invalidValuesDataProvider()
    {
        return [
            ['test'],
            [0]
        ];
    }
}
