<?php

namespace Oro\Bundle\EmbeddedFormBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EmbeddedFormBundle\Validator\Constraints\NoTags;
use Oro\Bundle\EmbeddedFormBundle\Validator\Constraints\NoTagsValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class NoTagsValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator()
    {
        return new NoTagsValidator();
    }

    /**
     * @dataProvider valuesWithoutErrors
     */
    public function testShouldValidateWithoutErrors(?string $value)
    {
        $constraint = new NoTags();
        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }

    public function valuesWithoutErrors(): array
    {
        return [
            'empty value' => [''],
            'null value' => [null],
            'value with spaces' => ['   '],
            'simple css' => [
                'div.test { color: #fefefe; }'
            ],
            'css with > selector' => [
                'div.test > div.test { color: #fefefe; }'
            ],
            'css with > content' => [
                'div.test:before { content: \'>\'; }'
            ],
            'css with < content' => [
                'div.test:before { content: \'< \'; }'
            ],
        ];
    }

    /**
     * @dataProvider valuesWithErrors
     */
    public function testShouldValidateWithErrors(string $value)
    {
        $constraint = new NoTags();
        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->message)
            ->assertRaised();
    }

    public function valuesWithErrors(): array
    {
        return [
            'value with closed style' => [
                'div.test { color: #fefefe; } </style>'
            ],
            'value with tags' => [
                'div.test { color: #fefefe; } <b>test</b>'
            ],
            'value with < in content' => [
                'div.test:before { content: \'<\'; }'
            ],
        ];
    }

    /**
     * @dataProvider invalidValues
     */
    public function testShouldFailWithInvalidValue(mixed $value, string $exceptionMessage)
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $constraint = new NoTags();
        $this->validator->validate($value, $constraint);
    }

    public function invalidValues(): array
    {
        return [
            'array' => [
                [1],
                'Expected argument of type "string", "array" given'
            ],
            'object' => [
                new \stdClass(),
                'Expected argument of type "string", "stdClass" given'
            ],
        ];
    }
}
