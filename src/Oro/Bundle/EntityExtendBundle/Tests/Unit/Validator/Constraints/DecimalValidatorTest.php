<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EntityExtendBundle\Validator\Constraints\Decimal;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\DecimalValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class DecimalValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ExecutionContextInterface
     */
    protected $context;

    /**
     * @var DecimalValidator
     */
    protected $validator;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->context   = $this->createMock(ExecutionContextInterface::class);
        $this->validator = new DecimalValidator();

        $this->validator->initialize($this->context);
    }

    /**
     * @param array $options
     * @param float $value
     * @param bool $violation
     *
     * @dataProvider validateDataProvider
     */
    public function testValidate(array $options, $value, $violation): void
    {
        $this->context->expects(($violation ? self::once() : self::never()))
            ->method('addViolation');

        $constraint = new Decimal($options);

        $this->validator->validate($value, $constraint);
    }

    public function validateDataProvider(): array
    {
        return [
            [['precision' => 10,   'scale' => 4   ], 171.9,                false],
            [['precision' => 4,    'scale' => 2   ], 42,                   false],
            [['precision' => 4,    'scale' => 2   ], 142,                  true ],
            [['precision' => 4,    'scale' => 2   ], 42.42,                false],
            [['precision' => 4,    'scale' => 2   ], 42.423,               true ],
            [['precision' => 4,    'scale' => null], 42,                   false],
            [['precision' => 4,    'scale' => null], 14214,                true ],
            [['precision' => 4,    'scale' => null], 42.42,                true ],
            [['precision' => 4,    'scale' => null], 42.423,               true ],
            [['precision' => null, 'scale' => 2   ], 42424242,             false],
            [['precision' => null, 'scale' => 2   ], 424242424,            true ],
            [['precision' => null, 'scale' => 2   ], 42.42,                false],
            [['precision' => null, 'scale' => 2   ], 42.423,               true ],
            [['precision' => null, 'scale' => null], 42,                   false],
            [['precision' => null, 'scale' => null], 2147483646,           false],
            [['precision' => null, 'scale' => null], 42.42,                true ],
            [['precision' => null, 'scale' => null], 42.423,               true ],
            [['precision' => 2,    'scale' => 2   ], 12345678912345789123, true ],
        ];
    }
}
