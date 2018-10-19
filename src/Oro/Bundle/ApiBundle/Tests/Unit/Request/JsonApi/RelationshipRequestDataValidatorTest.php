<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request\JsonApi;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\JsonApi\RelationshipRequestDataValidator;

class RelationshipRequestDataValidatorTest extends \PHPUnit\Framework\TestCase
{
    /** @var RelationshipRequestDataValidator */
    private $validator;

    protected function setUp()
    {
        parent::setUp();

        $this->validator = new RelationshipRequestDataValidator();
    }

    /**
     * @dataProvider validResourceIdentifierObjectProvider
     */
    public function testValidResourceIdentifierObject($requestData)
    {
        $errors = $this->validator->validateResourceIdentifierObject($requestData);

        self::assertEmpty($errors);
    }

    public function validResourceIdentifierObjectProvider()
    {
        return [
            [
                ['data' => ['type' => 'products', 'id' => '123']]
            ],
            [
                ['data' => null]
            ]
        ];
    }

    /**
     * @dataProvider validResourceIdentifierObjectCollectionProvider
     */
    public function testValidResourceIdentifierObjectCollection($requestData)
    {
        $errors = $this->validator->validateResourceIdentifierObjectCollection($requestData);

        self::assertEmpty($errors);
    }

    public function validResourceIdentifierObjectCollectionProvider()
    {
        return [
            [
                ['data' => [['type' => 'products', 'id' => '123']]]
            ],
            [
                ['data' => []]
            ]
        ];
    }

    /**
     * @dataProvider invalidResourceIdentifierObjectProvider
     */
    public function testInvalidResourceIdentifierObject($requestData, $expectedErrors)
    {
        $errors = $this->validator->validateResourceIdentifierObject($requestData);

        $expectedErrorObjects = [];
        foreach ($expectedErrors as $expectedError) {
            $expectedErrorObjects[] = Error::createValidationError(Constraint::REQUEST_DATA, $expectedError[0])
                ->setSource(ErrorSource::createByPointer($expectedError[1]));
        }
        self::assertEquals($expectedErrorObjects, $errors);
    }

    public function invalidResourceIdentifierObjectProvider()
    {
        return [
            [
                [],
                [
                    ['The "data" top-level section should exist', '/data']
                ]
            ],
            [
                [123],
                [
                    ['The "data" top-level section should exist', '/data']
                ]
            ],
            [
                ['data' => []],
                [
                    ['The resource identifier object should be not empty object', '/data']
                ]
            ],
            [
                ['data' => 123],
                [
                    ['The resource identifier object should be NULL or an object', '/data']
                ]
            ],
            [
                ['data' => [123]],
                [
                    ['The resource identifier object should be an object', '/data']
                ]
            ],
            [
                ['data' => ['attr1' => 'val1']],
                [
                    ['The \'type\' property is required', '/data/type'],
                    ['The \'id\' property is required', '/data/id']
                ]
            ],
            [
                ['data' => ['type' => null, 'id' => null]],
                [
                    ['The \'type\' property should not be null', '/data/type'],
                    ['The \'id\' property should not be null', '/data/id']
                ]
            ],
            [
                ['data' => ['type' => '', 'id' => '']],
                [
                    ['The \'type\' property should not be blank', '/data/type'],
                    ['The \'id\' property should not be blank', '/data/id']
                ]
            ],
            [
                ['data' => ['type' => ' ', 'id' => ' ']],
                [
                    ['The \'type\' property should not be blank', '/data/type'],
                    ['The \'id\' property should not be blank', '/data/id']
                ]
            ],
            [
                ['data' => ['type' => 123, 'id' => 456]],
                [
                    ['The \'type\' property should be a string', '/data/type'],
                    ['The \'id\' property should be a string', '/data/id']
                ]
            ]
        ];
    }

    /**
     * @dataProvider invalidResourceIdentifierObjectCollectionProvider
     */
    public function testInvalidResourceIdentifierObjectCollection($requestData, $expectedErrors)
    {
        $errors = $this->validator->validateResourceIdentifierObjectCollection($requestData);

        $expectedErrorObjects = [];
        foreach ($expectedErrors as $expectedError) {
            $expectedErrorObjects[] = Error::createValidationError(Constraint::REQUEST_DATA, $expectedError[0])
                ->setSource(ErrorSource::createByPointer($expectedError[1]));
        }
        self::assertEquals($expectedErrorObjects, $errors);
    }

    public function invalidResourceIdentifierObjectCollectionProvider()
    {
        return [
            [
                [],
                [
                    ['The "data" top-level section should exist', '/data']
                ]
            ],
            [
                [123],
                [
                    ['The "data" top-level section should exist', '/data']
                ]
            ],
            [
                ['data' => null],
                [
                    ['The list of resource identifier objects should be an array', '/data']
                ]
            ],
            [
                ['data' => 123],
                [
                    ['The list of resource identifier objects should be an array', '/data']
                ]
            ],
            [
                ['data' => [123]],
                [
                    ['The resource identifier object should be an object', '/data/0']
                ]
            ],
            [
                ['data' => ['attr1' => 'val1']],
                [
                    ['The list of resource identifier objects should be an array', '/data']
                ]
            ],
            [
                ['data' => [['attr1' => 'val1']]],
                [
                    ['The \'type\' property is required', '/data/0/type'],
                    ['The \'id\' property is required', '/data/0/id']
                ]
            ],
            [
                ['data' => [['type' => null, 'id' => null]]],
                [
                    ['The \'type\' property should not be null', '/data/0/type'],
                    ['The \'id\' property should not be null', '/data/0/id']
                ]
            ],
            [
                ['data' => [['type' => '', 'id' => '']]],
                [
                    ['The \'type\' property should not be blank', '/data/0/type'],
                    ['The \'id\' property should not be blank', '/data/0/id']
                ]
            ],
            [
                ['data' => [['type' => ' ', 'id' => ' ']]],
                [
                    ['The \'type\' property should not be blank', '/data/0/type'],
                    ['The \'id\' property should not be blank', '/data/0/id']
                ]
            ],
            [
                ['data' => [['type' => 123, 'id' => 456]]],
                [
                    ['The \'type\' property should be a string', '/data/0/type'],
                    ['The \'id\' property should be a string', '/data/0/id']
                ]
            ]
        ];
    }
}
