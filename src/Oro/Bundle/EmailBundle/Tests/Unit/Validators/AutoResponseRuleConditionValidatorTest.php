<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Validator;

use Oro\Bundle\EmailBundle\Entity\AutoResponseRuleCondition;
use Oro\Bundle\EmailBundle\Validator\AutoResponseRuleConditionValidator;
use Oro\Bundle\EmailBundle\Validator\Constraints\AutoResponseRuleCondition as AutoResponseRuleConditionConstraint;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Form\Type\Filter\TextFilterType;

class AutoResponseRuleConditionValidatorTest extends \PHPUnit_Framework_TestCase
{
    protected $context;

    protected $constraint;
    protected $validator;

    public function setUp()
    {
        $this->constraint = new AutoResponseRuleConditionConstraint();
        $this->context = $this->getMock('Symfony\Component\Validator\ExecutionContextInterface');
    }

    /**
     * @dataProvider validDataProvider
     */
    public function testValidData(AutoResponseRuleCondition $condition)
    {
        $this->context->expects($this->never())
            ->method('addViolation');

        $this->getValidator()->validate($condition, $this->constraint);
    }

    public function validDataProvider()
    {
        return [
            [$this->createCondition(FilterUtility::TYPE_EMPTY, '')],
            [$this->createCondition(FilterUtility::TYPE_NOT_EMPTY, '')],
            [$this->createCondition(TextFilterType::TYPE_NOT_CONTAINS, 'not empty')],
            [$this->createCondition(TextFilterType::TYPE_EQUAL, 'not empty')],
            [$this->createCondition(TextFilterType::TYPE_STARTS_WITH, 'not empty')],
            [$this->createCondition(TextFilterType::TYPE_ENDS_WITH, 'not empty')],
            [$this->createCondition(TextFilterType::TYPE_IN, 'not empty')],
            [$this->createCondition(TextFilterType::TYPE_NOT_IN, 'not empty')],
        ];
    }

    /**
     * @dataProvider invalidDataProvider
     */
    public function testInvalidData(AutoResponseRuleCondition $condition, $message)
    {
        $this->context->expects($this->once())
            ->method('addViolation')
            ->with($message);

        $this->getValidator()->validate($condition, $this->constraint);
    }

    public function invalidDataProvider()
    {
        $constraint = new AutoResponseRuleConditionConstraint();

        return [
            [$this->createCondition(FilterUtility::TYPE_EMPTY, 'not empty'), $constraint->nonEmptyInputMessage],
            [
                $this->createCondition(FilterUtility::TYPE_NOT_EMPTY, 'not empty'),
                $constraint->nonEmptyInputMessage,
            ],
            [$this->createCondition(TextFilterType::TYPE_NOT_CONTAINS, ''), $constraint->emptyInputMessage],
            [$this->createCondition(TextFilterType::TYPE_EQUAL, ''), $constraint->emptyInputMessage],
            [$this->createCondition(TextFilterType::TYPE_STARTS_WITH, ''), $constraint->emptyInputMessage],
            [$this->createCondition(TextFilterType::TYPE_ENDS_WITH, ''), $constraint->emptyInputMessage],
            [$this->createCondition(TextFilterType::TYPE_IN, ''), $constraint->emptyInputMessage],
            [$this->createCondition(TextFilterType::TYPE_NOT_IN, ''), $constraint->emptyInputMessage],
        ];
    }

    /**
     * @param string $filterType
     * @param string $filterValue
     *
     * @return AutoResponseRuleCondition
     */
    protected function createCondition($filterType, $filterValue)
    {
        $condition = new AutoResponseRuleCondition();

        return $condition
            ->setFilterType($filterType)
            ->setFilterValue($filterValue);
    }

    /**
     * @return AutoResponseRuleConditionValidator
     */
    protected function getValidator()
    {
        $validator = new AutoResponseRuleConditionValidator($this->emailAddressHelper);
        $validator->initialize($this->context);

        return $validator;
    }
}
