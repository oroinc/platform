<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EntityExtendBundle\Validator\Constraints\UniqueKeys;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\UniqueKeysValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class UniqueKeysValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $context;

    /**
     * @var UniqueKeysValidator
     */
    protected $validator;

    protected function setUp()
    {
        $this->context = $this->createMock(ExecutionContextInterface::class);

        $this->validator = new UniqueKeysValidator();
        $this->validator->initialize($this->context);
    }

    /**
     * @param array $value
     * @param bool  $violation
     *
     * @dataProvider validateDataProvider
     */
    public function testValidate(array $value, $violation)
    {
        $constraint = new UniqueKeys();

        if ($violation) {
            $this->context
                ->expects($this->once())
                ->method('addViolation')
                ->with($this->equalTo($constraint->message));
        }

        $this->validator->validate($value, $constraint);
    }

    /**
     * @return array
     */
    public function validateDataProvider()
    {
        return [
            'empty'           => [[], false],
            'name_not_unique' => [
                [
                    [
                        'name' => 'non_unique',
                        'key'  => [
                            'field1',
                            'field2'
                        ]
                    ],
                    [
                        'name' => 'non_unique',
                        'key'  => [
                            'field3',
                            'field4'
                        ]
                    ]
                ],
                true
            ],
            'keys_not_unique' => [
                [
                    [
                        'name' => 'name1',
                        'key'  => [
                            'field1',
                            'field2'
                        ]
                    ],
                    [
                        'name' => 'name2',
                        'key'  => [
                            'field1',
                            'field2'
                        ]
                    ]
                ],
                true
            ],
            'same_field'      => [
                [
                    [
                        'name' => 'name1',
                        'key'  => [
                            'field1',
                            'field2'
                        ]
                    ],
                    [
                        'name' => 'name2',
                        'key'  => [
                            'field1',
                            'field3'
                        ]
                    ]
                ],
                false
            ],
            'unique_fields'   => [
                [
                    [
                        'name' => 'name1',
                        'key'  => [
                            'field1',
                            'field2'
                        ]
                    ],
                    [
                        'name' => 'name2',
                        'key'  => [
                            'field3',
                            'field4'
                        ]
                    ]
                ],
                false
            ]
        ];
    }
}
