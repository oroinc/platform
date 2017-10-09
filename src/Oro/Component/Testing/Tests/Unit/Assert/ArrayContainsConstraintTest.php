<?php

namespace Oro\Component\Testing\Tests\Unit\Assert;

use Oro\Component\Testing\Assert\ArrayContainsConstraint;

class ArrayContainsConstraintTest extends \PHPUnit_Framework_TestCase
{
    public function testNull()
    {
        $expected = ['key' => 'value'];
        $actual = null;
        $expectedMessage = <<<TEXT
Failed asserting that the array contains other array.
Errors:
Path: "". Error: Failed asserting that null is of type "array".
TEXT;

        $this->expectException(\PHPUnit_Framework_ExpectationFailedException::class);
        $this->expectExceptionMessage($expectedMessage);

        $constraint = new ArrayContainsConstraint($expected);
        $constraint->evaluate($actual);
    }

    public function testNotArray()
    {
        $expected = ['key' => 'value'];
        $actual = 'test';
        $expectedMessage = <<<TEXT
Failed asserting that the array contains other array.
Errors:
Path: "". Error: Failed asserting that 'test' is of type "array".
TEXT;

        $this->expectException(\PHPUnit_Framework_ExpectationFailedException::class);
        $this->expectExceptionMessage($expectedMessage);

        $constraint = new ArrayContainsConstraint($expected);
        $constraint->evaluate($actual);
    }

    public function testArraysEquals()
    {
        $expected = ['key' => 'value'];
        $actual = ['key' => 'value'];

        $constraint = new ArrayContainsConstraint($expected);
        $constraint->evaluate($actual);
    }

    public function testArraysEqualsForIntKey()
    {
        $expected = ['value'];
        $actual = ['value'];

        $constraint = new ArrayContainsConstraint($expected);
        $constraint->evaluate($actual);
    }

    public function testArrayContains()
    {
        $expected = ['key' => 'value'];
        $actual = ['key' => 'value', 'anotherKey' => 'anotherValue'];

        $constraint = new ArrayContainsConstraint($expected);
        $constraint->evaluate($actual);
    }

    public function testArrayContainsForIntKey()
    {
        $expected = ['value'];
        $actual = ['value', 'anotherValue'];

        $constraint = new ArrayContainsConstraint($expected);
        $constraint->evaluate($actual);
    }

    public function testArraysEqualsForMultiDimensionalArrays()
    {
        $expected = [
            'key1' => 'value1',
            'key2' => [
                'key21' => 'value21',
                'key22' => [
                    'key221' => 'value221',
                ]
            ]
        ];
        $actual = [
            'key1' => 'value1',
            'key2' => [
                'key21' => 'value21',
                'key22' => [
                    'key221' => 'value221',
                ]
            ]
        ];

        $constraint = new ArrayContainsConstraint($expected);
        $constraint->evaluate($actual);
    }

    public function testArraysEqualsForMultiDimensionalArraysWithIntKeys()
    {
        $expected = [
            'value1',
            [
                'value21',
                [
                    'value221',
                ]
            ]
        ];
        $actual = [
            'value1',
            [
                'value21',
                [
                    'value221',
                ]
            ]
        ];

        $constraint = new ArrayContainsConstraint($expected);
        $constraint->evaluate($actual);
    }

    public function testArrayContainsForMultiDimensionalArrays()
    {
        $expected = [
            'key1' => 'value1',
            'key2' => [
                'key21' => 'value21',
                'key22' => [
                    'key221' => 'value221',
                ]
            ]
        ];
        $actual = [
            'key1' => 'value1',
            'key2' => [
                'key21' => 'value21',
                'key22' => [
                    'key221' => 'value221',
                    'key222' => 'value222',
                ],
                'key23' => 'value23'
            ],
            'key3' => 'value3'
        ];

        $constraint = new ArrayContainsConstraint($expected);
        $constraint->evaluate($actual);
    }

    public function testArrayContainsForMultiDimensionalArraysWithIntKeys()
    {
        $expected = [
            'value1',
            [
                'value21',
                [
                    'value221',
                ]
            ]
        ];
        $actual = [
            'value1',
            [
                'value21',
                [
                    'value221',
                    'value222',
                ],
                'value23'
            ],
            'value3'
        ];

        $constraint = new ArrayContainsConstraint($expected);
        $constraint->evaluate($actual);
    }

    public function testStrictArrayContainsForIntKeyAndIndexOfElementIsDifferent()
    {
        $expected = ['value1', 'value2'];
        $actual = ['value1', 'anotherValue', 'value2'];
        $expectedMessage = <<<TEXT
Failed asserting that the array contains other array.
Errors:
Path: "1". Error: Failed asserting that 'anotherValue' is identical to 'value2'.
TEXT;

        $this->expectException(\PHPUnit_Framework_ExpectationFailedException::class);
        $this->expectExceptionMessage($expectedMessage);

        $constraint = new ArrayContainsConstraint($expected);
        $constraint->evaluate($actual);
    }

    public function testNotStrictArrayContainsForIntKeyAndIndexOfElementIsDifferent()
    {
        $expected = ['value1', 'value2'];
        $actual = ['value1', 'anotherValue', 'value2'];

        $constraint = new ArrayContainsConstraint($expected, false);
        $constraint->evaluate($actual);
    }

    public function testStrictArrayContainsForIntKeyAndOrderOfElementIsDifferent()
    {
        $expected = ['value1', 'value2'];
        $actual = ['value2', 'anotherValue', 'value1'];
        $expectedMessage = <<<TEXT
Failed asserting that the array contains other array.
Errors:
Path: "0". Error: Failed asserting that 'value2' is identical to 'value1'.
Path: "1". Error: Failed asserting that 'anotherValue' is identical to 'value2'.
TEXT;

        $this->expectException(\PHPUnit_Framework_ExpectationFailedException::class);
        $this->expectExceptionMessage($expectedMessage);

        $constraint = new ArrayContainsConstraint($expected);
        $constraint->evaluate($actual);
    }

    public function testNotStrictArrayContainsForIntKeyAndOrderOfElementIsDifferent()
    {
        $expected = ['value1', 'value2'];
        $actual = ['value2', 'anotherValue', 'value1'];

        $constraint = new ArrayContainsConstraint($expected, false);
        $constraint->evaluate($actual);
    }

    public function testStrictArrayContainsWhenMoreThen10ErrorsFound()
    {
        $expected = [
            'value1',
            'value2',
            'value3',
            'value4',
            'value5',
            'value6',
            'value7',
            'value8',
            'value9',
            'value10',
            'value11',
            'value12',
        ];
        $actual = [];
        $expectedMessage = <<<TEXT
Failed asserting that the array contains other array.
Errors:
Path: "0". Error: Failed asserting that an array has the key 0.
Path: "1". Error: Failed asserting that an array has the key 1.
Path: "2". Error: Failed asserting that an array has the key 2.
Path: "3". Error: Failed asserting that an array has the key 3.
Path: "4". Error: Failed asserting that an array has the key 4.
Path: "5". Error: Failed asserting that an array has the key 5.
Path: "6". Error: Failed asserting that an array has the key 6.
Path: "7". Error: Failed asserting that an array has the key 7.
Path: "8". Error: Failed asserting that an array has the key 8.
Path: "9". Error: Failed asserting that an array has the key 9.
and others ...
TEXT;

        $this->expectException(\PHPUnit_Framework_ExpectationFailedException::class);
        $this->expectExceptionMessage($expectedMessage);

        $constraint = new ArrayContainsConstraint($expected);
        $constraint->evaluate($actual);
    }

    public function testArrayContainsWhenMultiDimensionalArraysAreDifferent()
    {
        $expected = [
            'key1' => 'value1',
            'key2' => [
                'key21' => 'value21',
                'key22' => [
                    'key221' => 'value221',
                ]
            ]
        ];
        $actual = [
            'key1' => 'value1',
            'key2' => [
                'key21' => 'value21',
                'key22' => [
                    'key221' => 'value221_other',
                ]
            ]
        ];
        $expectedMessage = <<<TEXT
Failed asserting that the array contains other array.
Errors:
Path: "key2.key22.key221". Error: Failed asserting that 'value221_other' is identical to 'value221'.
TEXT;

        $this->expectException(\PHPUnit_Framework_ExpectationFailedException::class);
        $this->expectExceptionMessage($expectedMessage);

        $constraint = new ArrayContainsConstraint($expected);
        $constraint->evaluate($actual);
    }

    public function testStrictArrayContainsForMultiDimensionalArraysWithIntKeyAndOrderOfElementIsDifferent()
    {
        $expected = [
            'key1' => 'value1',
            'key2' => [
                ['key' => 'value221'],
                ['key' => 'value222'],
            ]
        ];
        $actual = [
            'key1' => 'value1',
            'key2' => [
                ['key' => 'value222'],
                ['key' => 'value221'],
            ]
        ];
        $expectedMessage = <<<TEXT
Failed asserting that the array contains other array.
Errors:
Path: "key2.0.key". Error: Failed asserting that 'value222' is identical to 'value221'.
Path: "key2.1.key". Error: Failed asserting that 'value221' is identical to 'value222'.
TEXT;

        $this->expectException(\PHPUnit_Framework_ExpectationFailedException::class);
        $this->expectExceptionMessage($expectedMessage);

        $constraint = new ArrayContainsConstraint($expected);
        $constraint->evaluate($actual);
    }

    public function testNotStrictArrayContainsForMultiDimensionalArraysWithIntKeyAndOrderOfElementIsDifferent()
    {
        $expected = [
            'key1' => 'value1',
            'key2' => [
                ['key' => 'value221'],
                ['key' => 'value222'],
            ]
        ];
        $actual = [
            'key1' => 'value1',
            'key2' => [
                ['key' => 'value222'],
                ['key' => 'value221'],
            ]
        ];

        $constraint = new ArrayContainsConstraint($expected, false);
        $constraint->evaluate($actual);
    }

    public function testNotStrictArrayContainsForMultiDimensionalArraysWithIntKeyAndHasErrors()
    {
        $expected = [
            'key1' => 'value1',
            'key2' => [
                ['key' => 'value221'],
                ['key' => 'value222'],
            ]
        ];
        $actual = [
            'key1' => 'value1',
            'key2' => [
                ['key' => ['value222']],
                ['key' => ['value221']],
            ]
        ];
        $expectedMessage = <<<TEXT
Failed asserting that the array contains other array.
Errors:
Path: "key2.0.key". Error: Failed asserting that Array &0 (
    0 => 'value222'
) is identical to 'value221'.
Path: "key2.1.key". Error: Failed asserting that Array &0 (
    0 => 'value221'
) is identical to 'value222'.
TEXT;

        $this->expectException(\PHPUnit_Framework_ExpectationFailedException::class);
        $this->expectExceptionMessage($expectedMessage);

        $constraint = new ArrayContainsConstraint($expected, false);
        $constraint->evaluate($actual);
    }

    public function testNotStrictArrayContainsForMultiDimensionalArraysWithIntKeyAndHasErrorsOnDifferentLevels()
    {
        $expected = [
            'key1' => 'value1',
            'key2' => [
                ['key1' => 'value1', 'key2' => 'value2'],
            ]
        ];
        $actual = [
            'key1' => 'value1',
            'key2' => [
                ['key1' => 'value1'],
                ['key2' => 'value2'],
                ['key1' => 1, 'key2' => 2],
            ]
        ];
        $expectedMessage = <<<TEXT
Failed asserting that the array contains other array.
Errors:
Path: "key2.0.key2". Error: Failed asserting that an array has the key 'key2'.
TEXT;

        $this->expectException(\PHPUnit_Framework_ExpectationFailedException::class);
        $this->expectExceptionMessage($expectedMessage);

        $constraint = new ArrayContainsConstraint($expected, false);
        $constraint->evaluate($actual);
    }
}
