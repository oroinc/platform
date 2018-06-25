<?php

namespace Oro\Bundle\ReportBundle\Tests\Unit\Validator;

use Oro\Bundle\ReportBundle\Entity\Report;
use Oro\Bundle\ReportBundle\Validator\ReportColumnDuplicateValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ReportColumnDuplicateValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Constraint|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $constraint;

    /**
     * @var ReportColumnDuplicateValidator
     */
    protected $validator;

    /**
     * @var ExecutionContextInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $context;

    public function setUp()
    {
        $this->constraint = $this->getMockBuilder(Constraint::class)->getMock();
        $this->context = $this->getMockBuilder(ExecutionContextInterface::class)->getMock();
        $this->validator = new ReportColumnDuplicateValidator();
        $this->validator->initialize($this->context);
    }

    public function testValidateShouldAddViolation()
    {
        $this->context->expects($this->once())->method('addViolation');
        $report = new Report();
        $report->setDefinition(json_encode([
            'columns' => [
                0 => [
                    'name' => 'Test',
                    'func' => 'testFunc',
                    'label' => 'Test'
                ],
                1 => [
                    'name' => 'Test',
                    'func' => 'testFunc',
                    'label' => 'Test'
                ]
            ]
        ]));
        $this->validator->validate($report, $this->constraint);
    }

    public function testValidateShouldDoNothingIfNotReport()
    {
        $this->context->expects($this->never())->method('addViolation');
        $this->validator->validate(null, $this->constraint);
    }

    public function testValidateShouldDoNothingIfColumnsNotDuplicated()
    {
        $this->context->expects($this->never())->method('addViolation');
        $report = new Report();
        $report->setDefinition(json_encode([
            'columns' => [
                0 => [
                    'name' => 'Test',
                    'func' => 'testFunc',
                    'label' => 'Test'
                ],
                1 => [
                    'name' => 'Test',
                    'func' => ''
                ],
                2 => [
                    'name' => 'Test',
                    'func' => [
                        'name' => 'test'
                    ]
                ]
            ]
        ]));
        $this->validator->validate($report, $this->constraint);
    }
}
