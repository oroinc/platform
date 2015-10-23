<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EntityExtendBundle\Validator\Constraints\DecimalValidator;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\Decimal;

class DecimalValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Symfony\Component\Validator\ExecutionContextInterface
     */
    protected $context;

    /**
     * @var DecimalValidator
     */
    protected $validator;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->context   = $this->getMock('Symfony\Component\Validator\ExecutionContextInterface');
        $this->validator = new DecimalValidator();

        $this->validator->initialize($this->context);
    }

    /**
     * @param $options
     * @param $value
     * @param $violation
     *
     * @dataProvider validateDataProvider
     */
    public function testValidate($options, $value, $violation)
    {
        $this->context->expects(($violation ? $this->once() : $this->never()))
            ->method('addViolation');

        $constraint = new Decimal($options);

        $this->validator->validate($value, $constraint);
    }

    /**
     * @return array
     */
    public function validateDataProvider()
    {
        return [
            [['precision' => 4,    'scale' => 2   ], 42,          false],
            [['precision' => 4,    'scale' => 2   ], 142,         true ],
            [['precision' => 4,    'scale' => 2   ], 42.42,       false],
            [['precision' => 4,    'scale' => 2   ], 42.423,      true ],
            [['precision' => 4,    'scale' => null], 42,          false],
            [['precision' => 4,    'scale' => null], 14214,       true ],
            [['precision' => 4,    'scale' => null], 42.42,       true ],
            [['precision' => 4,    'scale' => null], 42.423,      true ],
            [['precision' => null, 'scale' => 2   ], 42424242,    false],
            [['precision' => null, 'scale' => 2   ], 424242424,   true ],
            [['precision' => null, 'scale' => 2   ], 42.42,       false],
            [['precision' => null, 'scale' => 2   ], 42.423,      true ],
            [['precision' => null, 'scale' => null], 42,          false],
            [['precision' => null, 'scale' => null], 2147483646,  false],
            [['precision' => null, 'scale' => null], 42.42,       true ],
            [['precision' => null, 'scale' => null], 42.423,      true ],
        ];
    }
}
