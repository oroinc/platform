<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ApiBundle\Form\Type\BooleanType;
use Oro\Bundle\ApiBundle\Tests\Unit\Form\ApiFormTypeTestCase;

class BooleanTypeTest extends ApiFormTypeTestCase
{
    /**
     * @dataProvider validValuesDataProvider
     */
    public function testWithValidValue($value, $expected)
    {
        /**
         * @see \Oro\Bundle\ApiBundle\Processor\Shared\SubmitForm::prepareRequestData
         */
        if (false === $value) {
            $value = 'false';
        }

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
            [false, false],
            [0, false]
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
            ['test'],
            [''],
            [null]
        ];
    }
}
