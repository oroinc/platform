<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EntityExtendBundle\Validator\Constraints\NotPhpKeyword;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\NotPhpKeywordValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class NotPhpKeywordValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator()
    {
        return new NotPhpKeywordValidator();
    }

    /**
     * @dataProvider validateDataProvider
     */
    public function testValidate(string $value, bool $valid)
    {
        $constraint = new NotPhpKeyword();
        $this->validator->validate($value, $constraint);

        if ($valid) {
            $this->assertNoViolation();
        } else {
            $this->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value)
                ->assertRaised();
        }
    }

    public function validateDataProvider(): array
    {
        return [
            ['', true],
            ['test', true],
            ['class', false],
            ['CLASS', false],
        ];
    }
}
