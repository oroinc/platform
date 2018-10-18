<?php

namespace Oro\Bundle\ReportBundle\Tests\Unit\Validator;

use Oro\Bundle\QueryDesignerBundle\Form\Type\DateGroupingType;
use Oro\Bundle\ReportBundle\Entity\Report;
use Oro\Bundle\ReportBundle\Validator\Constraints\ReportDateGroupingConstraint;
use Oro\Bundle\ReportBundle\Validator\ReportDateGroupingValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ReportDateGroupingValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ReportDateGroupingValidator
     */
    protected $reportDateGroupingValidator;

    /**
     * @var ReportDateGroupingConstraint|\PHPUnit\Framework\MockObject\MockObject $constraint
     */
    protected $constraint;

    /**
     * @var ExecutionContextInterface|\PHPUnit\Framework\MockObject\MockObject $constraint
     */
    protected $context;

    protected function setUp()
    {
        $this->context = $this->getMockBuilder(ExecutionContextInterface::class)->getMock();
        $this->reportDateGroupingValidator = new ReportDateGroupingValidator();
        $this->reportDateGroupingValidator->initialize($this->context);
        $this->constraint = $this->getMockBuilder(ReportDateGroupingConstraint::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testValidateReturnsIfNotReport()
    {
        $this->context->expects($this->never())
            ->method('addViolation');
        $this->reportDateGroupingValidator->validate(null, $this->constraint);
    }

    public function testValidateIgnoreIfNoDateGrouping()
    {
        $value = new Report();
        $value->setDefinition(null);
        $this->context->expects($this->never())
            ->method('addViolation');
        $this->reportDateGroupingValidator->validate($value, $this->constraint);
    }

    public function testValidateScreamsIfNoGroupingAvailable()
    {
        $value = new Report();
        $value->setDefinition(json_encode([DateGroupingType::DATE_GROUPING_NAME => []]));
        $this->context->expects($this->once())
            ->method('addViolation')
            ->with((new ReportDateGroupingConstraint())->groupByMandatoryMessage);

        $this->reportDateGroupingValidator->validate($value, $this->constraint);
    }

    public function testValidateScreamsIfNoFieldNameSet()
    {
        $value = new Report();
        $value->setDefinition(
            json_encode(
                [
                    DateGroupingType::DATE_GROUPING_NAME => [
                        DateGroupingType::USE_DATE_GROUPING_FILTER => true,
                    ],
                    'grouping_columns' => ['testGroup'],
                ]
            )
        );
        $this->context->expects($this->once())
            ->method('addViolation')
            ->with((new ReportDateGroupingConstraint())->dateFieldMandatoryMessage);

        $this->reportDateGroupingValidator->validate($value, $this->constraint);
    }
}
