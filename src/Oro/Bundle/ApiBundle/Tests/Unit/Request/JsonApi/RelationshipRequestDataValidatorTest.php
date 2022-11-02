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

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new RelationshipRequestDataValidator();
    }

    /**
     * @dataProvider validResourceIdentifierObjectProvider
     */
    public function testValidResourceIdentifierObject(array $requestData)
    {
        $errors = $this->validator->validateResourceIdentifierObject($requestData);

        self::assertEmpty($errors);
    }

    public function validResourceIdentifierObjectProvider(): array
    {
        return [
            [
                ['data' => ['type' => 'products', 'id' => '123']]
            ],
            [
                ['data' => null]
            ],
            [
                ['data' => null, 'jsonapi' => []]
            ],
            [
                ['data' => null, 'jsonapi' => ['test' => null]]
            ],
            [
                ['data' => null, 'meta' => []]
            ],
            [
                ['data' => null, 'meta' => ['test' => null]]
            ],
            [
                ['data' => null, 'links' => []]
            ],
            [
                ['data' => null, 'links' => ['test' => null]]
            ]
        ];
    }

    /**
     * @dataProvider validResourceIdentifierObjectCollectionProvider
     */
    public function testValidResourceIdentifierObjectCollection(array $requestData)
    {
        $errors = $this->validator->validateResourceIdentifierObjectCollection($requestData);

        self::assertEmpty($errors);
    }

    public function validResourceIdentifierObjectCollectionProvider(): array
    {
        return [
            [
                ['data' => [['type' => 'products', 'id' => '123']]]
            ],
            [
                ['data' => []]
            ],
            [
                ['data' => [], 'jsonapi' => []]
            ],
            [
                ['data' => [], 'jsonapi' => ['test' => null]]
            ],
            [
                ['data' => [], 'meta' => []]
            ],
            [
                ['data' => [], 'meta' => ['test' => null]]
            ],
            [
                ['data' => [], 'links' => []]
            ],
            [
                ['data' => [], 'links' => ['test' => null]]
            ]
        ];
    }

    /**
     * @dataProvider invalidResourceIdentifierObjectProvider
     */
    public function testInvalidResourceIdentifierObject(array $requestData, array $expectedErrors)
    {
        $errors = $this->validator->validateResourceIdentifierObject($requestData);

        $expectedErrorObjects = [];
        foreach ($expectedErrors as $expectedError) {
            $expectedErrorObjects[] = Error::createValidationError(Constraint::REQUEST_DATA, $expectedError[0])
                ->setSource(ErrorSource::createByPointer($expectedError[1]));
        }
        self::assertEquals($expectedErrorObjects, $errors);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function invalidResourceIdentifierObjectProvider(): array
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
            ],
            [
                ['data' => null, 'jsonapi' => null],
                [['The \'jsonapi\' property should be an array', '/jsonapi']]
            ],
            [
                ['data' => null, 'jsonapi' => 'test'],
                [['The \'jsonapi\' property should be an array', '/jsonapi']]
            ],
            [
                ['data' => null, 'jsonapi' => ['test']],
                [['The \'jsonapi\' property should be an associative array', '/jsonapi']]
            ],
            [
                ['data' => null, 'meta' => null],
                [['The \'meta\' property should be an array', '/meta']]
            ],
            [
                ['data' => null, 'meta' => 'test'],
                [['The \'meta\' property should be an array', '/meta']]
            ],
            [
                ['data' => null, 'meta' => ['test']],
                [['The \'meta\' property should be an associative array', '/meta']]
            ],
            [
                ['data' => null, 'links' => null],
                [['The \'links\' property should be an array', '/links']]
            ],
            [
                ['data' => null, 'links' => 'test'],
                [['The \'links\' property should be an array', '/links']]
            ],
            [
                ['data' => null, 'links' => ['test']],
                [['The \'links\' property should be an associative array', '/links']]
            ]
        ];
    }

    /**
     * @dataProvider invalidResourceIdentifierObjectCollectionProvider
     */
    public function testInvalidResourceIdentifierObjectCollection(array $requestData, array $expectedErrors)
    {
        $errors = $this->validator->validateResourceIdentifierObjectCollection($requestData);

        $expectedErrorObjects = [];
        foreach ($expectedErrors as $expectedError) {
            $expectedErrorObjects[] = Error::createValidationError(Constraint::REQUEST_DATA, $expectedError[0])
                ->setSource(ErrorSource::createByPointer($expectedError[1]));
        }
        self::assertEquals($expectedErrorObjects, $errors);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function invalidResourceIdentifierObjectCollectionProvider(): array
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
            ],
            [
                ['data' => [], 'jsonapi' => null],
                [['The \'jsonapi\' property should be an array', '/jsonapi']]
            ],
            [
                ['data' => [], 'jsonapi' => 'test'],
                [['The \'jsonapi\' property should be an array', '/jsonapi']]
            ],
            [
                ['data' => [], 'jsonapi' => ['test']],
                [['The \'jsonapi\' property should be an associative array', '/jsonapi']]
            ],
            [
                ['data' => [], 'meta' => null],
                [['The \'meta\' property should be an array', '/meta']]
            ],
            [
                ['data' => [], 'meta' => 'test'],
                [['The \'meta\' property should be an array', '/meta']]
            ],
            [
                ['data' => [], 'meta' => ['test']],
                [['The \'meta\' property should be an associative array', '/meta']]
            ],
            [
                ['data' => [], 'links' => null],
                [['The \'links\' property should be an array', '/links']]
            ],
            [
                ['data' => [], 'links' => 'test'],
                [['The \'links\' property should be an array', '/links']]
            ],
            [
                ['data' => [], 'links' => ['test']],
                [['The \'links\' property should be an associative array', '/links']]
            ]
        ];
    }
}
