<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EntityExtendBundle\Model\EnumValue;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class EnumValueValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Constraints\EnumValue
     */
    protected $constraint;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ExecutionContextInterface
     */
    protected $context;

    /**
     * @var Constraints\EnumValueValidator
     */
    protected $validator;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->context = $this->createMock(ExecutionContextInterface::class);

        $this->constraint = new Constraints\EnumValue();
        $this->validator = new Constraints\EnumValueValidator();
        $this->validator->initialize($this->context);
    }

    public function testConfiguration()
    {
        $this->assertEquals(
            'Oro\Bundle\EntityExtendBundle\Validator\Constraints\EnumValueValidator',
            $this->constraint->validatedBy()
        );

        $this->assertEquals([Constraint::CLASS_CONSTRAINT], $this->constraint->getTargets());
    }

    /**
     * @expectedException \Symfony\Component\Validator\Exception\UnexpectedTypeException
     */
    public function testNotEnumValueOrArray()
    {
        $this->validator->validate(new \stdClass(), $this->constraint);
    }

    /**
     * @param mixed $data
     * @param boolean $valid
     * @dataProvider validateProvider
     */
    public function testValidate($data, $valid)
    {
        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $this->context->expects($valid ? $this->never() : $this->once())
            ->method('buildViolation')
            ->willReturn($builder);
        $builder->expects($valid ? $this->never() : $this->once())
            ->method('atPath')
            ->willReturnSelf();
        $builder->expects($valid ? $this->never() : $this->once())
            ->method('setParameters')
            ->willReturnSelf();
        $builder->expects($valid ? $this->never() : $this->once())
            ->method('addViolation');

        $this->validator->validate($data, $this->constraint);
    }

    /**
     * @return array
     */
    public function validateProvider()
    {
        return [
            'empty' => [
                'data'      => new EnumValue(),
                'valid'     => true,
            ],
            'filled' => [
                'data'      => (new EnumValue())->setId('valId')->setLabel('valLabel'),
                'valid'     => true,
            ],
            'filled array' => [
                'data'      => [
                    'id' => 'valId',
                    'label' => 'valLabel',
                ],
                'valid'     => true,
            ],
            'wrong' => [
                'data'      => (new EnumValue())->setLabel('+'),
                'valid'     => false,
            ],
        ];
    }
}
