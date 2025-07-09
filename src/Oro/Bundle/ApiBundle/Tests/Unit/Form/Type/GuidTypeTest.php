<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ApiBundle\Form\Type\GuidType;
use Oro\Bundle\ApiBundle\Tests\Unit\Form\ApiFormTypeTestCase;

class GuidTypeTest extends ApiFormTypeTestCase
{
    /**
     * @dataProvider validValuesDataProvider
     */
    public function testWithValidValue(string $value): void
    {
        $form = $this->factory->create(GuidType::class);
        $form->submit($value);
        self::assertTrue($form->isSynchronized());
        self::assertSame($value, $form->getData());
    }

    public function validValuesDataProvider(): array
    {
        return [
            ['eac12975-d94d-4e96-88b1-101b99914def'],
            ['EAC12975-D94D-4E96-88B1-101B99914DEF']
        ];
    }

    /**
     * @dataProvider invalidValuesDataProvider
     */
    public function testWithInvalidValue(string $value): void
    {
        $form = $this->factory->create(GuidType::class);
        $form->submit($value);
        self::assertFalse($form->isSynchronized());
    }

    public function invalidValuesDataProvider(): array
    {
        return [
            [''],
            ['test'],
            ['7eab7435-44bb-493a-9bda-dea3fda3c0dh'],
            ['7eab7435-44bb-493a-9bda-dea3fda3c0d91']
        ];
    }
}
