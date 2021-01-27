<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\QueryDesignerBundle\Model\GroupNode;
use Oro\Bundle\QueryDesignerBundle\Model\Restriction;
use Oro\Bundle\QueryDesignerBundle\Validator\Constraints\GroupNodeConditions;
use Oro\Bundle\QueryDesignerBundle\Validator\Constraints\GroupNodeConditionsValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class GroupNodeConditionsValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): GroupNodeConditionsValidator
    {
        return new GroupNodeConditionsValidator();
    }

    public function testUnsupportedConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate(new GroupNode(FilterUtility::CONDITION_AND), $this->createMock(Constraint::class));
    }

    public function testNullValueIsValid(): void
    {
        $this->validator->validate(null, new GroupNodeConditions());
        $this->assertNoViolation();
    }

    public function testUnsupportedValue(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate(new \stdClass(), new GroupNodeConditions());
    }

    /**
     * @dataProvider validGroupNodesProvider
     */
    public function testValidGroupNodes(GroupNode $node): void
    {
        $this->validator->validate($node, new GroupNodeConditions());
        $this->assertNoViolation();
    }

    public function validGroupNodesProvider(): array
    {
        return [
            [new GroupNode(FilterUtility::CONDITION_AND)],
            [
                (new GroupNode(FilterUtility::CONDITION_AND))
                    ->addNode(new Restriction('a = 5', FilterUtility::CONDITION_AND, false))
            ],
            [
                (new GroupNode(FilterUtility::CONDITION_AND))
                    ->addNode(new Restriction('a = 5', FilterUtility::CONDITION_AND, true))
            ],
            [
                (new GroupNode(FilterUtility::CONDITION_AND))
                    ->addNode(new Restriction('a = 5', FilterUtility::CONDITION_AND, false))
                    ->addNode(new Restriction('a = 5', FilterUtility::CONDITION_AND, true))
            ],
            [
                (new GroupNode(FilterUtility::CONDITION_AND))
                    ->addNode(
                        (new GroupNode(FilterUtility::CONDITION_AND))
                            ->addNode(new Restriction('a = 5', FilterUtility::CONDITION_AND, false))
                    )
                    ->addNode(new Restriction('a = 5', FilterUtility::CONDITION_AND, false))
                    ->addNode(new Restriction('a = 5', FilterUtility::CONDITION_AND, true))
            ],
            [
                (new GroupNode(FilterUtility::CONDITION_AND))
                    ->addNode(new Restriction('a = 5', FilterUtility::CONDITION_AND, false))
                    ->addNode(
                        (new GroupNode(FilterUtility::CONDITION_AND))
                            ->addNode(new Restriction('a = 5', FilterUtility::CONDITION_AND, true))
                    )
            ],
        ];
    }

    /**
     * @dataProvider invalidGroupNodesProvider
     */
    public function testInvalidGroupNodes(GroupNode $node): void
    {
        $constraint = new GroupNodeConditions();
        $this->validator->validate($node, $constraint);
        $this->buildViolation($constraint->message)
            ->assertRaised();
    }

    public function invalidGroupNodesProvider(): array
    {
        return [
            [
                (new GroupNode(FilterUtility::CONDITION_AND))
                    ->addNode(new Restriction('a = 5', FilterUtility::CONDITION_AND, true))
                    ->addNode(new Restriction('a = 5', FilterUtility::CONDITION_AND, false))
            ],
            [
                (new GroupNode(FilterUtility::CONDITION_AND))
                    ->addNode(
                        (new GroupNode(FilterUtility::CONDITION_AND))
                            ->addNode(new Restriction('a = 5', FilterUtility::CONDITION_AND, true))
                    )
                    ->addNode(new Restriction('a = 5', FilterUtility::CONDITION_AND, false))
                    ->addNode(new Restriction('a = 5', FilterUtility::CONDITION_AND, true))
            ],
            [
                (new GroupNode(FilterUtility::CONDITION_AND))
                    ->addNode(
                        (new GroupNode(FilterUtility::CONDITION_AND))
                            ->addNode(new Restriction('a = 5', FilterUtility::CONDITION_AND, false))
                            ->addNode(new Restriction('a = 5', FilterUtility::CONDITION_AND, true))
                    )
                    ->addNode(new Restriction('a = 5', FilterUtility::CONDITION_AND, false))
                    ->addNode(new Restriction('a = 5', FilterUtility::CONDITION_AND, false))
            ],
        ];
    }
}
