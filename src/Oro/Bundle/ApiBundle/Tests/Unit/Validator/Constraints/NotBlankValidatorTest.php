<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Validator\Constraints\NotBlank;

use Oro\Component\Testing\Validator\AbstractConstraintValidatorTest;
use Oro\Bundle\ApiBundle\Validator\Constraints\NotBlankValidator;

class NotBlankValidatorTest extends AbstractConstraintValidatorTest
{
    protected function createValidator()
    {
        return new NotBlankValidator();
    }

    /**
     * @dataProvider getValidValues
     */
    public function testValidValues($value)
    {
        $this->validator->validate($value, new NotBlank());

        $this->assertNoViolation();
    }

    public function getValidValues()
    {
        return [
            ['foo'],
            [0],
            [0.0],
            ['0'],
            [123],
            [[123]],
            [new ArrayCollection([123])],
        ];
    }

    public function testNullIsInvalid()
    {
        $constraint = new NotBlank([
            'message' => 'myMessage',
        ]);

        $this->validator->validate(null, $constraint);

        $this->buildViolation('myMessage')
            ->setParameter('{{ value }}', 'null')
            ->setCode(NotBlank::IS_BLANK_ERROR)
            ->assertRaised();
    }

    public function testBlankIsInvalid()
    {
        $constraint = new NotBlank([
            'message' => 'myMessage',
        ]);

        $this->validator->validate('', $constraint);

        $this->buildViolation('myMessage')
            ->setParameter('{{ value }}', '""')
            ->setCode(NotBlank::IS_BLANK_ERROR)
            ->assertRaised();
    }

    public function testFalseIsInvalid()
    {
        $constraint = new NotBlank([
            'message' => 'myMessage',
        ]);

        $this->validator->validate(false, $constraint);

        $this->buildViolation('myMessage')
            ->setParameter('{{ value }}', 'false')
            ->setCode(NotBlank::IS_BLANK_ERROR)
            ->assertRaised();
    }

    public function testEmptyArrayIsInvalid()
    {
        $constraint = new NotBlank([
            'message' => 'myMessage',
        ]);

        $this->validator->validate([], $constraint);

        $this->buildViolation('myMessage')
            ->setParameter('{{ value }}', 'array')
            ->setCode(NotBlank::IS_BLANK_ERROR)
            ->assertRaised();
    }

    public function testEmptyCollectionIsInvalid()
    {
        $constraint = new NotBlank([
            'message' => 'myMessage',
        ]);

        $this->validator->validate(new ArrayCollection(), $constraint);

        $this->buildViolation('myMessage')
            ->setParameter('{{ value }}', 'object')
            ->setCode(NotBlank::IS_BLANK_ERROR)
            ->assertRaised();
    }
}
