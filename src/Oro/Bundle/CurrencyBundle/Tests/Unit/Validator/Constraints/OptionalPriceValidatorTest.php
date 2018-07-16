<?php

namespace Oro\Bundle\CurrencyBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Validator\Constraints;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class OptionalPriceValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Constraints\OptionalPrice
     */
    protected $constraint;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ExecutionContextInterface
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
        $this->context      = $this->createMock(ExecutionContextInterface::class);
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
        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $this->context->expects($isValid ? $this->never() : $this->once())
            ->method('buildViolation')
            ->with($this->constraint->message)
            ->willReturn($builder);
        $builder->expects($isValid ? $this->never() : $this->once())
            ->method('atPath')
            ->with('currency')
            ->willReturnSelf();
        $builder->expects($isValid ? $this->never() : $this->once())
            ->method('addViolation');

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
