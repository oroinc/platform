<?php

namespace Oro\Component\Testing\Tests\Unit\Assert;

use Oro\Component\Testing\Assert\ArrayContainsConstraint;

class ArrayContainsConstraintTest extends \PHPUnit\Framework\TestCase
{
    public function testNullActualData()
    {
        $expected = ['key' => 'value'];
        $actual = null;
        $expectedMessage = <<<TEXT
Failed asserting that the array contains other array.
Errors:
Path: "". Error: Failed asserting that null is of type "array".
TEXT;

        $this->expectException(\PHPUnit\Framework\ExpectationFailedException::class);
        $this->expectExceptionMessage($expectedMessage);

        $constraint = new ArrayContainsConstraint($expected);
        $constraint->evaluate($actual);
    }

    public function testNotArrayActualData()
    {
        $expected = ['key' => 'value'];
        $actual = 'test';
        $expectedMessage = <<<TEXT
Failed asserting that the array contains other array.
Errors:
Path: "". Error: Failed asserting that 'test' is of type "array".
TEXT;

        $this->expectException(\PHPUnit\Framework\ExpectationFailedException::class);
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

    public function testArrayContainsWhenExpectedArrayHasMoreItemsThanActualArray()
    {
        $expected = ['key' => 'value', 'anotherKey' => 'anotherValue'];
        $actual = ['key' => 'value'];
        $expectedMessage = <<<TEXT
Failed asserting that the array contains other array.
Errors:
Path: "anotherKey". Error: Failed asserting that an array has the key 'anotherKey'.
TEXT;

        $this->expectException(\PHPUnit\Framework\ExpectationFailedException::class);
        $this->expectExceptionMessage($expectedMessage);

        $constraint = new ArrayContainsConstraint($expected);
        $constraint->evaluate($actual);
    }

    public function testArrayContainsWhenExpectedArrayHasMoreItemsThanActualArrayForIntKey()
    {
        $expected = ['value', 'anotherValue'];
        $actual = ['value'];
        $expectedMessage = <<<TEXT
Failed asserting that the array contains other array.
Errors:
Path: "1". Error: Failed asserting that an array has the key 1.
TEXT;

        $this->expectException(\PHPUnit\Framework\ExpectationFailedException::class);
        $this->expectExceptionMessage($expectedMessage);

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

        $this->expectException(\PHPUnit\Framework\ExpectationFailedException::class);
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

        $this->expectException(\PHPUnit\Framework\ExpectationFailedException::class);
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

        $this->expectException(\PHPUnit\Framework\ExpectationFailedException::class);
        $this->expectExceptionMessage($expectedMessage);

        $constraint = new ArrayContainsConstraint($expected);
        $constraint->evaluate($actual);
    }

    public function testStrictArrayContainsWhenMultiDimensionalArraysAreDifferent()
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

        $this->expectException(\PHPUnit\Framework\ExpectationFailedException::class);
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

        $this->expectException(\PHPUnit\Framework\ExpectationFailedException::class);
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

        $this->expectException(\PHPUnit\Framework\ExpectationFailedException::class);
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

        $this->expectException(\PHPUnit\Framework\ExpectationFailedException::class);
        $this->expectExceptionMessage($expectedMessage);

        $constraint = new ArrayContainsConstraint($expected, false);
        $constraint->evaluate($actual);
    }

    public function testNotStrictArraysNotEqualForWhenSeveralNotMatchedItemsFoundBeforeCallOfTryMatchIndexedElement()
    {
        $expected = [
            [
                'key1' => 'value1',
                'key2' => [
                    'key21' => 'value21',
                    'key22' => [
                        'key221' => 'value221',
                        'key222' => 'value222',
                        'key223' => 'value223'
                    ]
                ],
                'key3' => [
                    'key31' => [
                        ['type' => 'test', 'key' => '1'],
                        ['type' => 'test', 'key' => '2']
                    ]
                ]
            ]
        ];
        $actual = [
            [
                'key1' => 'value1',
                'key2' => [
                    'key21' => 'value21',
                    'key22' => [
                        'key221' => 'value221',
                        'key222' => 'value222_a',
                        'key223' => 'value223_a'
                    ]
                ],
                'key3' => [
                    'key31' => [
                        ['type' => 'test', 'key' => '2'],
                        ['type' => 'test', 'key' => '1']
                    ]
                ]
            ]
        ];
        $expectedMessage = <<<TEXT
Failed asserting that the array contains other array.
Errors:
Path: "0.key2.key22.key222". Error: Failed asserting that 'value222_a' is identical to 'value222'.
Path: "0.key2.key22.key223". Error: Failed asserting that 'value223_a' is identical to 'value223'.
TEXT;

        $this->expectException(\PHPUnit\Framework\ExpectationFailedException::class);
        $this->expectExceptionMessage($expectedMessage);

        $constraint = new ArrayContainsConstraint($expected, false);
        $constraint->evaluate($actual);
    }

    public function testNotStrictArrayContainsWithNestedAttributesAndAndOrderOfElementIsDifferent()
    {
        $expected = [
            'data' => [
                ['id' => '3', 'attributes' => ['attr1' => 'val31']],
                ['id' => '1', 'attributes' => ['attr1' => 'val11']],
                ['id' => '2', 'attributes' => ['attr1' => 'val21']]
            ]
        ];
        $actual = [
            'data' => [
                ['id' => '1', 'attributes' => ['attr1' => 'val11']],
                ['id' => '2', 'attributes' => ['attr1' => 'val21']],
                ['id' => '3', 'attributes' => ['attr1' => 'val31']]
            ]
        ];

        $constraint = new ArrayContainsConstraint($expected, false);
        $constraint->evaluate($actual);
    }

    public function testNotStrictArrayContainsWhenNestedAttributeOfFirstItemIsNotEqualAndOrderOfNextItemsAreDifferent()
    {
        $expected = [
            'data' => [
                ['id' => '1', 'attributes' => ['attr1' => 'val11', 'attr2' => 'val12']],
                ['id' => '3', 'attributes' => ['attr1' => 'val31']],
                ['id' => '2', 'attributes' => ['attr1' => 'val21']]
            ]
        ];
        $actual = [
            'data' => [
                ['id' => '1', 'attributes' => ['attr1' => 'val11']],
                ['id' => '2', 'attributes' => ['attr1' => 'val21']],
                ['id' => '3', 'attributes' => ['attr1' => 'val31']]
            ]
        ];
        $expectedMessage = <<<TEXT
Failed asserting that the array contains other array.
Errors:
Path: "data.0.attributes.attr2". Error: Failed asserting that an array has the key 'attr2'.
TEXT;

        $this->expectException(\PHPUnit\Framework\ExpectationFailedException::class);
        $this->expectExceptionMessage($expectedMessage);

        $constraint = new ArrayContainsConstraint($expected, false);
        $constraint->evaluate($actual);
    }

    public function testNotStrictArrayContainsForTwoSimilarItemAndSameOrderOfItems()
    {
        $expected = [
            'data' => [
                ['attributes' => ['attr1' => 'val11']],
                ['attributes' => ['attr1' => 'val41']],
                ['attributes' => ['attr1' => 'val31']],
                ['attributes' => ['attr1' => 'val41', 'attr2' => 'val42']]
            ]
        ];
        $actual = [
            'data' => [
                ['attributes' => ['attr1' => 'val11', 'attr2' => 'val12']],
                ['attributes' => ['attr1' => 'val21', 'attr2' => 'val22']],
                ['attributes' => ['attr1' => 'val31', 'attr2' => 'val32']],
                ['attributes' => ['attr1' => 'val41', 'attr2' => 'val42']]
            ]
        ];
        $expectedMessage = <<<TEXT
Failed asserting that the array contains other array.
Errors:
Path: "data.1.attributes.attr1". Error: Failed asserting that 'val21' is identical to 'val41'.
TEXT;

        $this->expectException(\PHPUnit\Framework\ExpectationFailedException::class);
        $this->expectExceptionMessage($expectedMessage);

        $constraint = new ArrayContainsConstraint($expected, false);
        $constraint->evaluate($actual);
    }

    public function testNotStrictArrayContainsForTwoSimilarItemAndDifferentOrderOfItems()
    {
        $expected = [
            'data' => [
                ['attributes' => ['attr1' => 'val31']],
                ['attributes' => ['attr1' => 'val41']],
                ['attributes' => ['attr1' => 'val11']],
                ['attributes' => ['attr1' => 'val41', 'attr2' => 'val42']],
                ['attributes' => ['attr1' => 'val21']]
            ]
        ];
        $actual = [
            'data' => [
                ['attributes' => ['attr1' => 'val11', 'attr2' => 'val12']],
                ['attributes' => ['attr1' => 'val21', 'attr2' => 'val22']],
                ['attributes' => ['attr1' => 'val31', 'attr2' => 'val32']],
                ['attributes' => ['attr1' => 'val41', 'attr2' => 'val42']],
                ['attributes' => ['attr1' => 'val51', 'attr2' => 'val52']]
            ]
        ];
        $expectedMessage = <<<TEXT
Failed asserting that the array contains other array.
Errors:
Path: "data.1.attributes.attr1". Error: Failed asserting that 'val21' is identical to 'val41'.
Path: "data.4.attributes.attr1". Error: Failed asserting that 'val51' is identical to 'val21'.
TEXT;

        $this->expectException(\PHPUnit\Framework\ExpectationFailedException::class);
        $this->expectExceptionMessage($expectedMessage);

        $constraint = new ArrayContainsConstraint($expected, false);
        $constraint->evaluate($actual);
    }

    public function testNotStrictArrayContainsForTwoSimilarItemAndTheseItemsKeysDifferentThanInActualData()
    {
        $expected = [
            'data' => [
                ['attributes' => ['attr1' => 'val11']],
                ['attributes' => ['attr1' => 'val31']],
                ['attributes' => ['attr1' => 'val21']],
                ['attributes' => ['attr1' => 'val31', 'attr2' => 'val32']]
            ]
        ];
        $actual = [
            'data' => [
                ['attributes' => ['attr1' => 'val11', 'attr2' => 'val12']],
                ['attributes' => ['attr1' => 'val21', 'attr2' => 'val22']],
                ['attributes' => ['attr1' => 'val31', 'attr2' => 'val32']],
                ['attributes' => ['attr1' => 'val41', 'attr2' => 'val42']]
            ]
        ];
        $expectedMessage = <<<TEXT
Failed asserting that the array contains other array.
Errors:
Path: "data.3.attributes.attr1". Error: Failed asserting that 'val41' is identical to 'val31'.
Path: "data.3.attributes.attr2". Error: Failed asserting that 'val42' is identical to 'val32'.
TEXT;

        $this->expectException(\PHPUnit\Framework\ExpectationFailedException::class);
        $this->expectExceptionMessage($expectedMessage);

        $constraint = new ArrayContainsConstraint($expected, false);
        $constraint->evaluate($actual);
    }
}
