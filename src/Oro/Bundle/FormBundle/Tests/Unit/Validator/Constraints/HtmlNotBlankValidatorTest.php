<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\FormBundle\Validator\Constraints\HtmlNotBlank;
use Oro\Bundle\FormBundle\Validator\Constraints\HtmlNotBlankValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class HtmlNotBlankValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator()
    {
        return new HtmlNotBlankValidator();
    }

    /**
     * @dataProvider validItemsDataProvider
     */
    public function testValidateValid($value): void
    {
        $constraint = new HtmlNotBlank();
        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }

    public function validItemsDataProvider(): array
    {
        return [
            'html' => ['<p>some content</p>'],
            'image' => ['<p><img src="/"/></p>'],
            'text' => ['some content'],
        ];
    }

    /**
     * @dataProvider invalidItemsDataProvider
     */
    public function testValidateInvalid($value): void
    {
        $constraint = new HtmlNotBlank();
        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('{{ value }}', '""')
            ->setCode(HtmlNotBlank::IS_BLANK_ERROR)
            ->assertRaised();
    }

    public function invalidItemsDataProvider(): array
    {
        return [
            'empty string' => [''],
            'one white-space' => [' '],
            'few white-spaces' => ['     '],
            'false' => [false],
            'null' => [null],
            'empty html' => ['<p></p>'],
            'empty html with attr' => ['<p class="empty"></p>'],
        ];
    }
}
