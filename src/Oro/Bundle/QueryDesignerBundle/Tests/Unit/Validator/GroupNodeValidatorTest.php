<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Unit\Validator;

use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\QueryDesignerBundle\Model\GroupNode;
use Oro\Bundle\QueryDesignerBundle\Model\Restriction;
use Oro\Bundle\QueryDesignerBundle\Validator\Constraints\GroupNodeConstraint;
use Oro\Bundle\QueryDesignerBundle\Validator\GroupNodeValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class GroupNodeValidatorTest extends \PHPUnit\Framework\TestCase
{
    protected $executionContext;
    protected $validator;

    protected $constraint;

    public function setUp()
    {
        $this->executionContext = $this->createMock(ExecutionContextInterface::class);

        $this->validator = new GroupNodeValidator();
        $this->validator->initialize($this->executionContext);

        $this->constraint = new GroupNodeConstraint();
    }

    /**
     * @dataProvider validGroupNodesProvider
     */
    public function testValidGroupNodes(GroupNode $node)
    {
        $this->executionContext->expects($this->never())
            ->method('addViolation');

        $this->validator->validate($node, $this->constraint);
    }

    public function validGroupNodesProvider()
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
    public function testInvalidGroupNodes(GroupNode $node)
    {
        $this->executionContext->expects($this->once())
            ->method('addViolation')
            ->with($this->constraint->mixedConditionsMessage);

        $this->validator->validate($node, $this->constraint);
    }

    public function invalidGroupNodesProvider()
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
