<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request;

use Oro\Bundle\ApiBundle\Form\NamedValidationConstraint;
use Oro\Bundle\ApiBundle\Request\ConstraintTextExtractor;
use Oro\Bundle\ApiBundle\Validator\Constraints\HasAdderAndRemover;
use Oro\Bundle\SecurityBundle\Validator\Constraints\FieldAccessGranted;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Blank;
use Symfony\Component\Validator\Constraints\NotBlank;

class ConstraintTextExtractorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConstraintTextExtractor */
    private $constraintTextExtractor;

    protected function setUp()
    {
        $this->constraintTextExtractor = new ConstraintTextExtractor();
    }

    /**
     * @dataProvider getConstraintStatusCodeDataProvider()
     */
    public function testGetConstraintStatusCode(Constraint $constraint, $expectedStatusCode)
    {
        self::assertEquals(
            $expectedStatusCode,
            $this->constraintTextExtractor->getConstraintStatusCode($constraint)
        );
    }

    public function getConstraintStatusCodeDataProvider()
    {
        return [
            [new Blank(), 400],
            [new HasAdderAndRemover(['class' => 'Test\Class', 'property' => 'test']), 501],
            [new FieldAccessGranted(), 403]
        ];
    }

    public function testGetConstraintCode()
    {
        self::assertNull($this->constraintTextExtractor->getConstraintCode(new Blank()));
    }

    /**
     * @dataProvider getConstraintTypeDataProvider
     */
    public function testConstraintType(Constraint $constraint, $expectedType)
    {
        self::assertEquals(
            $expectedType,
            $this->constraintTextExtractor->getConstraintType($constraint)
        );
    }

    public function getConstraintTypeDataProvider()
    {
        return [
            [new Blank(), 'blank constraint'],
            [
                new HasAdderAndRemover(['class' => 'Test\Class', 'property' => 'test']),
                'has adder and remover constraint'
            ],
            [new NamedValidationConstraint(Constraint::class), 'constraint'],
            [new NamedValidationConstraint(NotBlank::class), 'not blank constraint'],
            [new NamedValidationConstraint('NotBlank'), 'not blank constraint'],
            [new NamedValidationConstraint('NotBlankConstraint'), 'not blank constraint'],
            [new NamedValidationConstraint('Not Blank'), 'not blank constraint'],
            [new NamedValidationConstraint('Not Blank Constraint'), 'not blank constraint'],
            [new NamedValidationConstraint('not blank'), 'not blank constraint'],
            [new NamedValidationConstraint('not blank constraint'), 'not blank constraint'],
            [new NamedValidationConstraint('not_blank'), 'not blank constraint'],
            [new NamedValidationConstraint('not_blank_constraint'), 'not blank constraint'],
            [new NamedValidationConstraint('not-blank'), 'not blank constraint'],
            [new NamedValidationConstraint('not-blank-constraint'), 'not blank constraint'],
            [new NamedValidationConstraint('not*blank'), 'not blank constraint'],
            [new NamedValidationConstraint('not*blank~constraint'), 'not blank constraint'],
            [new NamedValidationConstraint('IO'), 'io constraint'],
            [new NamedValidationConstraint('IOConstraint'), 'io constraint'],
            [new NamedValidationConstraint('PHP'), 'php constraint'],
            [new NamedValidationConstraint('PHPConstraint'), 'php constraint']
        ];
    }
}
