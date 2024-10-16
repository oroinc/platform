<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityExtendBundle\Form\Type\FixedIntegerType;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Validation;

class FixedIntegerTypeTest extends TypeTestCase
{
    /** @var FixedIntegerType */
    private $type;

    #[\Override]
    protected function setUp(): void
    {
        $this->type = new FixedIntegerType();

        parent::setUp();
    }


    public function testGetParent(): void
    {
        self::assertEquals(IntegerType::class, $this->type->getParent());
    }

    /**
     * @dataProvider validSubmitDataProvider
     */
    public function testValidSubmit($dataType, $value): void
    {
        $form = $this->factory->create(FixedIntegerType::class, null, ['data_type' => $dataType]);
        $form->submit($value);

        self::assertTrue($form->isSynchronized());
        self::assertEmpty($form->getErrors(true));
    }

    public static function validSubmitDataProvider(): array
    {
        return [
            'negative_smallint' => ['data_type' => 'smallint', 'value' => -32768],
            'positive_smallint' => ['data_type' => 'smallint', 'value' => 32767],
            'negative_integer' => ['data_type' => 'integer', 'value' => -2147483648],
            'positive_integer' => ['data_type' => 'integer', 'value' => 2147483647],
            'negative_bigint' => ['data_type' => 'bigint', 'value' => -9007199254740991],
            'positive_bigint' => ['data_type' => 'bigint', 'value' => 9007199254740991],
        ];
    }

    /**
     * @dataProvider notValidSubmitDataProvider
     */
    public function testNotValidSubmit(string $dataType, int $value, string $message): void
    {
        $form = $this->factory->create(FixedIntegerType::class, null, ['data_type' => $dataType]);
        $form->submit($value);
        self::assertTrue($form->isSynchronized());

        $errors = $form->getErrors(true);
        self::assertEquals(1, $errors->count());
        self::assertEquals($message, $form->getErrors()[0]->getMessage());
    }

    public static function notValidSubmitDataProvider(): array
    {
        return [
            'negative_smallint' => [
                'data_type' => 'smallint',
                'value' => -32769,
                'message' => 'This value should be between -32768 and 32767.'
            ],
            'positive_smallint' => [
                'data_type' => 'smallint',
                'value' => 32768,
                'message' => 'This value should be between -32768 and 32767.'
            ],
            'negative_integer' => [
                'data_type' => 'integer',
                'value' => -2147483649,
                'message' => 'This value should be between -2147483648 and 2147483647.'
            ],
            'positive_integer' => [
                'data_type' => 'integer',
                'value' => 2147483648,
                'message' => 'This value should be between -2147483648 and 2147483647.'
            ],
            'negative_bigint' => [
                'data_type' => 'bigint',
                'value' => -9007199254740992,
                'message' => 'This value should be between -9007199254740991 and 9007199254740991.'
            ],
            'positive_bigint' => [
                'data_type' => 'bigint',
                'value' => 9007199254740992,
                'message' => 'This value should be between -9007199254740991 and 9007199254740991.'
            ]
        ];
    }

    #[\Override]
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension([$this->type], []),
            new ValidatorExtension(Validation::createValidator())
        ];
    }
}
