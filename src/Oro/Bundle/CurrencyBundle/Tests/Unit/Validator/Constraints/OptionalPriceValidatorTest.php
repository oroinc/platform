<?php

namespace Oro\Bundle\CurrencyBundle\Tests\Unit\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ExecutionContextInterface;

use Oro\Bundle\CurrencyBundle\Validator\Constraints;
use Oro\Bundle\CurrencyBundle\Entity\Price;

class OptionalPriceValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Constraints\OptionalPrice
     */
    protected $constraint;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ExecutionContextInterface
     */
    protected $context;

    /**
     * @var Constraints\OptionalPriceValidator
     */
    protected $validator;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->context      = $this->getMock('Symfony\Component\Validator\ExecutionContextInterface');
        $this->constraint   = new Constraints\OptionalPrice();
        $this->validator    = new Constraints\OptionalPriceValidator();
        $this->validator->initialize($this->context);
    }

    public function testConfiguration()
    {
        $this->assertEquals([Constraint::CLASS_CONSTRAINT], $this->constraint->getTargets());
    }

    /**
     * @param boolean $isValid
     * @param mixed $inputData
     * @dataProvider validateProvider
     */
    public function testValidate($isValid, $inputData)
    {
        $this->context
            ->expects($isValid ? $this->never() : $this->once())
            ->method('addViolationAt')
            ->with('currency', $this->constraint->message)
        ;

        $this->validator->validate($inputData, $this->constraint);
    }

    /**
     * @return array
     */
    public function validateProvider()
    {
        return [
            'empty data' => [
                'isValid'   => true,
                'inputData' => Price::create(null, null),
            ],
            'empty value' => [
                'isValid'   => true,
                'inputData' => Price::create(null, 'USD'),
            ],
            'empty currency' => [
                'isValid'   => false,
                'inputData' => Price::create(11, null),
            ],
            'valid value' => [
                'isValid'   => true,
                'inputData' => Price::create(11, 'USD'),
            ],
        ];
    }
}
