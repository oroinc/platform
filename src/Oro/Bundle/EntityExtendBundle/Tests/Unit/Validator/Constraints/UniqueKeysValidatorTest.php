<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EntityExtendBundle\Validator\Constraints\UniqueKeys;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\UniqueKeysValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class UniqueKeysValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator()
    {
        return new UniqueKeysValidator();
    }

    /**
     * @dataProvider validateDataProvider
     */
    public function testValidate(array $value, bool $valid)
    {
        $constraint = new UniqueKeys();
        $this->validator->validate($value, $constraint);

        if ($valid) {
            $this->assertNoViolation();
        } else {
            $this->buildViolation($constraint->message)
                ->assertRaised();
        }
    }

    public function validateDataProvider(): array
    {
        return [
            'empty'           => [[], true],
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
                false
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
                false
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
                true
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
                true
            ]
        ];
    }
}
