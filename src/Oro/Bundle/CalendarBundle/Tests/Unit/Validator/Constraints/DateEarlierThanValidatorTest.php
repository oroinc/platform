<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\CalendarBundle\Validator\Constraints\DateEarlierThan;
use Oro\Bundle\CalendarBundle\Validator\Constraints\DateEarlierThanValidator;

class DateEarlierThanValidatorTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var DateEarlierThanValidator
     */
    protected $dateEarlierThanValidator;

    /**
    * @var \DateTime
    */
    protected $dateTimeStart;

    /**
     * @var \DateTime
     */
    protected $dateTimeEnd;

    /**
     * @var DateEarlierThan
     */
    protected $constraint;

    /**
     * @var \Symfony\Component\Validator\ExecutionContext
     */
    protected $context;

    protected $formField;

    protected function setUp()
    {
        $this->dateTimeStart = new \DateTime('-1 day');
        $this->dateTimeEnd   = new \DateTime('+1 day');
        $this->constraint    = new DateEarlierThan('end');
        $this->dateEarlierThanValidator = new DateEarlierThanValidator();

        $this->formField = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();

        $form = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();

        $form->expects($this->any())
            ->method('get')
            ->will($this->returnValue($this->formField));

        $getRootReturnValue = 'testValidateExceptionWhenInvalidRootType' == $this->getName() ? array() : $form;
        $this->context = $this->getMock('\Symfony\Component\Validator\ExecutionContextInterface');
        $this->context->expects($this->any())
            ->method('getRoot')
            ->will($this->returnValue($getRootReturnValue));

        $this->dateEarlierThanValidator->initialize($this->context);
    }

    /**
     * @expectedException \Symfony\Component\Validator\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Expected argument of type DateTime, boolean given
     */
    public function testValidateExceptionWhenInvalidArgumentType()
    {
        $constraint = $this->getMock('Symfony\Component\Validator\Constraint');
        $validator = new DateEarlierThanValidator();
        $validator->validate(false, $constraint);
    }

    /**
     * @expectedException \Symfony\Component\Validator\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Expected argument of type DateTime, object given
     */
    public function testValidateExceptionWhenInvalidConstraintType()
    {
        $this->formField->expects($this->any())
            ->method('getData')
            ->will($this->returnValue('string'));
        $this->dateEarlierThanValidator->validate($this->dateTimeStart, $this->constraint);
    }

    /**
     * @expectedException \Symfony\Component\Validator\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Expected argument of type FormInterface, array given
     */
    public function testValidateExceptionWhenInvalidRootType()
    {
        $this->formField->expects($this->any())
            ->method('getData')
            ->will($this->returnValue($this->dateTimeEnd));

        $this->dateEarlierThanValidator->validate($this->dateTimeStart, $this->constraint);
    }


    public function testValidData()
    {
        $this->formField->expects($this->any())
            ->method('getData')
            ->will($this->returnValue($this->dateTimeEnd));

        $this->context->expects($this->never())
            ->method('addViolation');

        $this->dateEarlierThanValidator->validate($this->dateTimeStart, $this->constraint);
    }

    public function testInvalidData()
    {
        $this->formField->expects($this->any())
            ->method('getData')
            ->will($this->returnValue($this->dateTimeStart));

        $this->context->expects($this->once())
            ->method('addViolation')
            ->with(
                $this->equalTo($this->constraint->message),
                $this->equalTo(array('{{ ' . $this->constraint->getDefaultOption() . ' }}' => $this->constraint->field))
            );

        $this->dateEarlierThanValidator->validate($this->dateTimeEnd, $this->constraint);
    }
}
