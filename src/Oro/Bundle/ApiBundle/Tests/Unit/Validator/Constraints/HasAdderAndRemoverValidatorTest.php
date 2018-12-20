<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;
use Oro\Bundle\ApiBundle\Validator\Constraints\HasAdderAndRemover;
use Oro\Bundle\ApiBundle\Validator\Constraints\HasAdderAndRemoverValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class HasAdderAndRemoverValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator()
    {
        return new HasAdderAndRemoverValidator();
    }

    public function testNullIsValid()
    {
        $this->validator->validate(
            null,
            new HasAdderAndRemover(['class' => 'Test\Class', 'property' => 'testProperty'])
        );

        $this->assertNoViolation();
    }

    public function testHasAdderAndRemover()
    {
        $constraint = new HasAdderAndRemover(
            [
                'class'    => Entity\User::class,
                'property' => 'groups'
            ]
        );

        $this->validator->validate([new \stdClass()], $constraint);

        $this->assertNoViolation();
    }

    public function testNoAdderAndRemover()
    {
        $constraint = new HasAdderAndRemover(
            [
                'class'    => Entity\EntityWithoutGettersAndSetters::class,
                'property' => 'groups'
            ]
        );

        $this->validator->validate([new \stdClass()], $constraint);

        $this->buildViolation($constraint->message)
            ->setParameters(
                [
                    '{{ class }}'   => $constraint->class,
                    '{{ adder }}'   => 'addGroup',
                    '{{ remover }}' => 'removeGroup'
                ]
            )
            ->assertRaised();
    }

    public function testNoAdderAndRemoverButSeveralPairsPossible()
    {
        $constraint = new HasAdderAndRemover(
            [
                'class'    => Entity\EntityWithoutGettersAndSetters::class,
                'property' => 'appendices'
            ]
        );

        $this->validator->validate([new \stdClass()], $constraint);

        $this
            ->buildViolation(
                sprintf(
                    $constraint->severalPairsMessage,
                    '"{{ adder1 }}" and "{{ remover1 }}"'
                    . ' or "{{ adder2 }}" and "{{ remover2 }}"'
                    . ' or "{{ adder3 }}" and "{{ remover3 }}"'
                )
            )
            ->setParameters(
                [
                    '{{ class }}'    => $constraint->class,
                    '{{ adder1 }}'   => 'addAppendex',
                    '{{ remover1 }}' => 'removeAppendex',
                    '{{ adder2 }}'   => 'addAppendix',
                    '{{ remover2 }}' => 'removeAppendix',
                    '{{ adder3 }}'   => 'addAppendice',
                    '{{ remover3 }}' => 'removeAppendice'
                ]
            )
            ->assertRaised();
    }
}
