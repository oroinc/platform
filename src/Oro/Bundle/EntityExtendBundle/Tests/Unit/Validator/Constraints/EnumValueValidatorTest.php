<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ExecutionContextInterface;

use Oro\Bundle\EntityExtendBundle\Model\EnumValue;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints;

class EnumValueValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Constraints\EnumValue
     */
    protected $constraint;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ExecutionContextInterface
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
        $this->context = $this->getMock('Symfony\Component\Validator\ExecutionContextInterface');

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
        $this->context->expects($valid ? $this->never() : $this->once())
            ->method('addViolationAt');

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
