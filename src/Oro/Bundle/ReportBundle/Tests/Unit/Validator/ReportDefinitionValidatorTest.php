<?php

namespace Oro\Bundle\ReportBundle\Tests\Unit\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

use Oro\Bundle\ReportBundle\Entity\Report;
use Oro\Bundle\ReportBundle\Validator\ReportDefinitionValidator;

class ReportDefinitionValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Constraint|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $constraint;

    /**
     * @var ReportDefinitionValidator
     */
    protected $validator;

    /**
     * @var ExecutionContextInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $context;

    public function setUp()
    {
        $this->constraint = $this->getMockBuilder(Constraint::class)->getMock();
        $this->context = $this->getMockBuilder(ExecutionContextInterface::class)->getMock();
        $this->validator = new ReportDefinitionValidator();
        $this->validator->initialize($this->context);
    }

    public function testValidateShouldDoNothingIfNotReport()
    {
        $this->context->expects($this->never())->method('addViolation');
        $this->validator->validate(null, $this->constraint);
    }

    public function testValidateShouldAddViolation()
    {
        $this->context->expects($this->once())->method('addViolation');
        $this->validator->validate(new Report(), $this->constraint);
    }

    public function testValidateShouldDoNothingIfColumnsPresent()
    {
        $this->context->expects($this->never())->method('addViolation');
        $report = new Report();
        $report->setDefinition(json_encode(['columns' => []]));
        $this->validator->validate($report, $this->constraint);
    }
}
