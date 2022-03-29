<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\FormBundle\Validator\Constraints\RegExpSyntax;
use Oro\Bundle\FormBundle\Validator\Constraints\RegExpSyntaxValidator;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class RegExpSyntaxValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): ConstraintValidator
    {
        return new RegExpSyntaxValidator();
    }

    public function testNoViolationWhenValueIsEmpty(): void
    {
        $constraint = new RegExpSyntax();
        $this->validator->validate(null, $constraint);
        $this->validator->validate('', $constraint);
        $this->assertNoViolation();
    }

    public function testViolationWhenInvalidRegExp(): void
    {
        $constraint = new RegExpSyntax();
        $value = 'invalid~regexp';

        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('{{ reason }}', '"preg_match(): Unknown modifier \'r\'"')
            ->setParameter('{{ value }}', '"~' . $value . '~i"')
            ->setCode(RegExpSyntax::INVALID_REGEXP_SYNTAX_ERROR)
            ->assertRaised();
    }

    public function testNoViolationWhenValidRegExp(): void
    {
        $constraint = new RegExpSyntax();
        $value = '^(valid\sregexp)$';

        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }
}
