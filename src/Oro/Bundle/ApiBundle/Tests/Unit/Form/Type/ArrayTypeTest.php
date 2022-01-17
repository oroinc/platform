<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ApiBundle\Form\Type\ArrayType;
use Oro\Bundle\ApiBundle\Tests\Unit\Form\ApiFormTypeTestCase;

class ArrayTypeTest extends ApiFormTypeTestCase
{
    /**
     * @dataProvider validValuesDataProvider
     */
    public function testWithValidValue(array $value, array $expected)
    {
        $form = $this->factory->create(ArrayType::class);
        $form->submit($value);
        self::assertTrue($form->isSynchronized());
        self::assertSame($expected, $form->getData());
    }

    public function validValuesDataProvider(): array
    {
        return [
            [[], []],
            [[1, 2], [1, 2]],
            [['key' => 'value'], ['key' => 'value']]
        ];
    }

    /**
     * @dataProvider invalidValuesDataProvider
     */
    public function testWithInvalidValue(mixed $value)
    {
        $form = $this->factory->create(ArrayType::class);
        $form->submit($value);
        self::assertFalse($form->isSynchronized());
    }

    public function invalidValuesDataProvider(): array
    {
        return [
            ['test'],
            [0],
            [''],
            [null]
        ];
    }
}
