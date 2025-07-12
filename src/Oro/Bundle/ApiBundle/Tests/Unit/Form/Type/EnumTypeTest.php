<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ApiBundle\Form\Type\EnumType;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Model\BackedEnumInt;
use Oro\Bundle\ApiBundle\Tests\Unit\Form\ApiFormTypeTestCase;

class EnumTypeTest extends ApiFormTypeTestCase
{
    public function testWithValidValue(): void
    {
        $form = $this->factory->create(EnumType::class, null, ['class' => BackedEnumInt::class]);
        $form->submit('Item1');
        self::assertTrue($form->isSynchronized());
        self::assertSame(BackedEnumInt::Item1, $form->getData());
    }

    /**
     * @dataProvider invalidValuesDataProvider
     */
    public function testWithInvalidValue(string $value): void
    {
        $form = $this->factory->create(EnumType::class, null, ['class' => BackedEnumInt::class]);
        $form->submit($value);
        self::assertFalse($form->isSynchronized());
    }

    public function invalidValuesDataProvider(): array
    {
        return [
            [''],
            ['UndefinedItem']
        ];
    }
}
