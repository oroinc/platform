<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request\JsonApi;

use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\JsonApi\TypedRequestDataValidator;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class TypedRequestDataValidatorTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ValueNormalizer */
    private $valueNormalizer;

    /** @var RequestType */
    private $requestType;

    /** @var TypedRequestDataValidator */
    private $validator;

    protected function setUp()
    {
        parent::setUp();

        $this->valueNormalizer = $this->createMock(ValueNormalizer::class);
        $this->requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);

        $this->validator = new TypedRequestDataValidator(function ($entityType) {
            return ValueNormalizerUtil::convertToEntityClass(
                $this->valueNormalizer,
                $entityType,
                $this->requestType,
                false
            );
        });

        $this->valueNormalizer->expects(self::any())
            ->method('normalizeValue')
            ->with('products')
            ->willReturn(Product::class);
    }

    /**
     * @param array  $expectedErrors
     * @param array  $expectedPointers
     * @param string $expectedTitle
     * @param int    $expectedStatusCode
     * @param array  $errors
     */
    private function assertValidationErrors(
        array $expectedErrors,
        array $expectedPointers,
        string $expectedTitle,
        int $expectedStatusCode,
        array $errors
    ) {
        self::assertCount(count($expectedErrors), $errors);
        foreach ($errors as $key => $error) {
            self::assertEquals($expectedTitle, $error->getTitle());
            self::assertEquals($expectedErrors[$key], $error->getDetail());
            self::assertEquals($expectedPointers[$key], $error->getSource()->getPointer());
            self::assertEquals($expectedStatusCode, $error->getStatusCode());
        }
    }

    /**
     * @dataProvider validResourceObjectProvider
     */
    public function testValidResourceObject($requestData)
    {
        $errors = $this->validator->validateResourceObject(
            $requestData,
            false,
            Product::class
        );

        self::assertEmpty($errors);
    }

    /**
     * @dataProvider validResourceObjectWithIncludedResourcesProvider
     */
    public function testValidResourceObjectWithIncludedResources($requestData)
    {
        $errors = $this->validator->validateResourceObject(
            $requestData,
            true,
            Product::class
        );

        self::assertEmpty($errors);
    }

    public function validResourceObjectProvider()
    {
        return [
            [
                ['data' => ['type' => 'products']]
            ],
            [
                ['data' => ['type' => 'products', 'id' => '1']]
            ],
            [
                ['data' => ['type' => 'products', 'attributes' => ['test' => null]]]
            ],
            [
                ['data' => ['type' => 'products', 'relationships' => ['test' => ['data' => null]]]]
            ],
            [
                ['data' => ['type' => 'products', 'relationships' => ['test' => ['data' => []]]]]
            ]
        ];
    }

    public function validResourceObjectWithIncludedResourcesProvider()
    {
        return array_merge($this->validResourceObjectProvider(), [
            [
                [
                    'data'     => ['type' => 'products'],
                    'included' => [
                        ['type' => 'products', 'id' => '10']
                    ]
                ]
            ],
            [
                [
                    'data'     => ['type' => 'products'],
                    'included' => [
                        ['type' => 'products', 'id' => '10', 'attributes' => ['test' => null]]
                    ]
                ]
            ],
            [
                [
                    'data'     => ['type' => 'products'],
                    'included' => [
                        ['type' => 'products', 'id' => '10', 'relationships' => ['test' => ['data' => null]]]
                    ]
                ]
            ],
            [
                [
                    'data'     => ['type' => 'products'],
                    'included' => [
                        ['type' => 'products', 'id' => '10', 'relationships' => ['test' => ['data' => []]]]
                    ]
                ]
            ]
        ]);
    }

    /**
     * @dataProvider validResourceObjectCollectionProvider
     */
    public function testValidResourceObjectCollection($requestData)
    {
        $errors = $this->validator->validateResourceObjectCollection(
            $requestData,
            false,
            Product::class
        );

        self::assertEmpty($errors);
    }

    /**
     * @dataProvider validResourceObjectCollectionWithIncludedResourcesProvider
     */
    public function testValidResourceObjectCollectionWithIncludedResources($requestData)
    {
        $errors = $this->validator->validateResourceObjectCollection(
            $requestData,
            true,
            Product::class
        );

        self::assertEmpty($errors);
    }

    public function validResourceObjectCollectionProvider()
    {
        return [
            [
                ['data' => [['type' => 'products']]]
            ],
            [
                ['data' => [['type' => 'products', 'id' => '1']]]
            ],
            [
                ['data' => [['type' => 'products', 'attributes' => ['test' => null]]]]
            ],
            [
                ['data' => [['type' => 'products', 'relationships' => ['test' => ['data' => null]]]]]
            ],
            [
                ['data' => [['type' => 'products', 'relationships' => ['test' => ['data' => []]]]]]
            ]
        ];
    }

    public function validResourceObjectCollectionWithIncludedResourcesProvider()
    {
        return array_merge($this->validResourceObjectCollectionProvider(), [
            [
                [
                    'data'     => [['type' => 'products']],
                    'included' => [
                        ['type' => 'products', 'id' => '10']
                    ]
                ]
            ],
            [
                [
                    'data'     => [['type' => 'products']],
                    'included' => [
                        ['type' => 'products', 'id' => '10', 'attributes' => ['test' => null]]
                    ]
                ]
            ],
            [
                [
                    'data'     => [['type' => 'products']],
                    'included' => [
                        ['type' => 'products', 'id' => '10', 'relationships' => ['test' => ['data' => null]]]
                    ]
                ]
            ],
            [
                [
                    'data'     => [['type' => 'products']],
                    'included' => [
                        ['type' => 'products', 'id' => '10', 'relationships' => ['test' => ['data' => []]]]
                    ]
                ]
            ]
        ]);
    }

    /**
     * @dataProvider invalidResourceObjectProvider
     */
    public function testInvalidResourceObject(
        $requestData,
        $expectedError,
        $pointer,
        $title = Constraint::REQUEST_DATA,
        $statusCode = 400
    ) {
        $errors = $this->validator->validateResourceObject(
            $requestData,
            false,
            Product::class
        );

        $this->assertValidationErrors((array)$expectedError, (array)$pointer, $title, $statusCode, $errors);
    }

    /**
     * @dataProvider invalidResourceObjectWithIncludedResourcesProvider
     */
    public function testInvalidResourceObjectWithIncludedResources(
        $requestData,
        $expectedError,
        $pointer,
        $title = Constraint::REQUEST_DATA,
        $statusCode = 400
    ) {
        $errors = $this->validator->validateResourceObject(
            $requestData,
            true,
            Product::class
        );

        $this->assertValidationErrors((array)$expectedError, (array)$pointer, $title, $statusCode, $errors);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function invalidResourceObjectProvider()
    {
        return [
            [[], 'The primary data object should exist', '/data'],
            [['data' => null], 'The primary data object should not be empty', '/data'],
            [['data' => []], 'The primary data object should not be empty', '/data'],
            [['data' => ['test']], 'The \'type\' property is required', '/data/type'],
            [
                ['data' => ['id' => null, 'type' => 'products', 'attributes' => ['test' => null]]],
                'The \'id\' property should not be null',
                '/data/id'
            ],
            [
                ['data' => ['id' => '', 'type' => 'products', 'attributes' => ['test' => null]]],
                'The \'id\' property should not be blank',
                '/data/id'
            ],
            [
                ['data' => ['id' => ' ', 'type' => 'products', 'attributes' => ['test' => null]]],
                'The \'id\' property should not be blank',
                '/data/id'
            ],
            [
                ['data' => ['id' => 1, 'type' => 'products', 'attributes' => ['test' => null]]],
                'The \'id\' property should be a string',
                '/data/id'
            ],
            [
                ['data' => ['attributes' => ['foo' => 'bar']]],
                'The \'type\' property is required',
                '/data/type'
            ],
            [
                ['data' => ['type' => 'test', 'attributes' => ['foo' => 'bar']]],
                'The \'type\' property of the primary data object should match the requested resource',
                '/data/type',
                Constraint::CONFLICT,
                409
            ],
            [
                ['data' => ['type' => 'products', 'attributes' => null]],
                'The \'attributes\' property should be an array',
                '/data/attributes'
            ],
            [
                ['data' => ['type' => 'products', 'attributes' => []]],
                'The \'attributes\' property should not be empty',
                '/data/attributes'
            ],
            [
                ['data' => ['type' => 'products', 'attributes' => [1, 2, 3]]],
                'The \'attributes\' property should be an associative array',
                '/data/attributes'
            ],
            [
                ['data' => ['type' => 'products', 'relationships' => null]],
                'The \'relationships\' property should be an array',
                '/data/relationships'
            ],
            [
                ['data' => ['type' => 'products', 'relationships' => []]],
                'The \'relationships\' property should not be empty',
                '/data/relationships'
            ],
            [
                ['data' => ['type' => 'products', 'relationships' => [1, 2, 3]]],
                'The \'relationships\' property should be an associative array',
                '/data/relationships'
            ],
            [
                ['data' => ['type' => 'products', 'relationships' => ['test' => null]]],
                'The relationship should have \'data\' property',
                '/data/relationships/test'
            ],
            [
                ['data' => ['type' => 'products', 'relationships' => ['test' => []]]],
                'The relationship should have \'data\' property',
                '/data/relationships/test'
            ],
            [
                ['data' => ['type' => 'products', 'relationships' => ['test' => ['data' => ['id' => '1']]]]],
                'The \'type\' property is required',
                '/data/relationships/test/data/type'
            ],
            [
                [
                    'data' => [
                        'type'          => 'products',
                        'relationships' => ['test' => ['data' => ['type' => 'products']]]
                    ]
                ],
                'The \'id\' property is required',
                '/data/relationships/test/data/id'
            ],
            [
                [
                    'data' => [
                        'type'          => 'products',
                        'relationships' => ['test' => ['data' => [['id' => '1']]]]
                    ]
                ],
                'The \'type\' property is required',
                '/data/relationships/test/data/0/type'
            ],
            [
                [
                    'data' => [
                        'type'          => 'products',
                        'relationships' => ['test' => ['data' => [['type' => 'products']]]]
                    ]
                ],
                'The \'id\' property is required',
                '/data/relationships/test/data/0/id'
            ],
            [
                [
                    'data' => [
                        'type'          => 'products',
                        'relationships' => ['test' => ['data' => [['type' => 'products', 'id' => null]]]]
                    ]
                ],
                'The \'id\' property should not be null',
                '/data/relationships/test/data/0/id'
            ],
            [
                [
                    'data' => [
                        'type'          => 'products',
                        'relationships' => ['test' => ['data' => [['type' => 'products', 'id' => '']]]]
                    ]
                ],
                'The \'id\' property should not be blank',
                '/data/relationships/test/data/0/id'
            ],
            [
                [
                    'data' => [
                        'type'          => 'products',
                        'relationships' => ['test' => ['data' => [['type' => 'products', 'id' => ' ']]]]
                    ]
                ],
                'The \'id\' property should not be blank',
                '/data/relationships/test/data/0/id'
            ],
            [
                [
                    'data' => [
                        'type'          => 'products',
                        'relationships' => ['test' => ['data' => [['type' => 'products', 'id' => 1]]]]
                    ]
                ],
                'The \'id\' property should be a string',
                '/data/relationships/test/data/0/id'
            ]
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function invalidResourceObjectWithIncludedResourcesProvider()
    {
        return array_merge($this->invalidResourceObjectProvider(), [
            [
                ['data' => ['type' => 'products'], 'included' => null],
                'The \'included\' property should be an array',
                '/included'
            ],
            [
                ['data' => ['type' => 'products'], 'included' => []],
                'The \'included\' property should not be empty',
                '/included'
            ],
            [
                [
                    'data'     => ['type' => 'products'],
                    'included' => [
                        'test'
                    ]
                ],
                ['The related resource should be an object'],
                ['/included/0']
            ],
            [
                [
                    'data'     => ['type' => 'products'],
                    'included' => [
                        []
                    ]
                ],
                ['The \'type\' property is required', 'The \'id\' property is required'],
                ['/included/0/type', '/included/0/id']
            ],
            [
                [
                    'data'     => ['type' => 'products'],
                    'included' => [
                        ['type' => 'products']
                    ]
                ],
                'The \'id\' property is required',
                '/included/0/id'
            ],
            [
                [
                    'data'     => ['type' => 'products'],
                    'included' => [
                        ['type' => 'products', 'id' => null]
                    ]
                ],
                'The \'id\' property should not be null',
                '/included/0/id'
            ],
            [
                [
                    'data'     => ['type' => 'products'],
                    'included' => [
                        ['type' => 'products', 'id' => '']
                    ]
                ],
                'The \'id\' property should not be blank',
                '/included/0/id'
            ],
            [
                [
                    'data'     => ['type' => 'products'],
                    'included' => [
                        ['type' => 'products', 'id' => ' ']
                    ]
                ],
                'The \'id\' property should not be blank',
                '/included/0/id'
            ],
            [
                [
                    'data'     => ['type' => 'products'],
                    'included' => [
                        ['id' => '10']
                    ]
                ],
                'The \'type\' property is required',
                '/included/0/type'
            ],
            [
                [
                    'data'     => ['type' => 'products'],
                    'included' => [
                        ['type' => null, 'id' => '10']
                    ]
                ],
                'The \'type\' property should not be null',
                '/included/0/type'
            ],
            [
                [
                    'data'     => ['type' => 'products'],
                    'included' => [
                        ['type' => '', 'id' => '10']
                    ]
                ],
                'The \'type\' property should not be blank',
                '/included/0/type'
            ],
            [
                [
                    'data'     => ['type' => 'products'],
                    'included' => [
                        ['type' => ' ', 'id' => '10']
                    ]
                ],
                'The \'type\' property should not be blank',
                '/included/0/type'
            ]
        ]);
    }

    /**
     * @dataProvider invalidResourceObjectCollectionProvider
     */
    public function testInvalidResourceObjectCollection(
        $requestData,
        $expectedError,
        $pointer,
        $title = Constraint::REQUEST_DATA,
        $statusCode = 400
    ) {
        $errors = $this->validator->validateResourceObjectCollection(
            $requestData,
            false,
            Product::class
        );

        $this->assertValidationErrors((array)$expectedError, (array)$pointer, $title, $statusCode, $errors);
    }

    /**
     * @dataProvider invalidResourceObjectCollectionWithIncludedResourcesProvider
     */
    public function testInvalidResourceObjectCollectionWithIncludedResources(
        $requestData,
        $expectedError,
        $pointer,
        $title = Constraint::REQUEST_DATA,
        $statusCode = 400
    ) {
        $errors = $this->validator->validateResourceObjectCollection(
            $requestData,
            true,
            Product::class
        );

        $this->assertValidationErrors((array)$expectedError, (array)$pointer, $title, $statusCode, $errors);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function invalidResourceObjectCollectionProvider()
    {
        return [
            [[], 'The primary data object collection should exist', '/data'],
            [['data' => null], 'The primary data object collection should not be empty', '/data'],
            [['data' => []], 'The primary data object collection should not be empty', '/data'],
            [['data' => 'test'], 'The primary data object collection should be an array', '/data'],
            [['data' => ['key' => 'value']], 'The primary data object collection should be an array', '/data'],
            [['data' => [null]], 'The primary resource object should be an object', '/data/0'],
            [['data' => ['test']], 'The primary resource object should be an object', '/data/0'],
            [['data' => [[]]], 'The \'type\' property is required', '/data/0/type'],
            [
                ['data' => [['id' => null, 'type' => 'products', 'attributes' => ['test' => null]]]],
                'The \'id\' property should not be null',
                '/data/0/id'
            ],
            [
                ['data' => [['id' => '', 'type' => 'products', 'attributes' => ['test' => null]]]],
                'The \'id\' property should not be blank',
                '/data/0/id'
            ],
            [
                ['data' => [['id' => ' ', 'type' => 'products', 'attributes' => ['test' => null]]]],
                'The \'id\' property should not be blank',
                '/data/0/id'
            ],
            [
                ['data' => [['id' => 1, 'type' => 'products', 'attributes' => ['test' => null]]]],
                'The \'id\' property should be a string',
                '/data/0/id'
            ],
            [
                ['data' => [['attributes' => ['foo' => 'bar']]]],
                'The \'type\' property is required',
                '/data/0/type'
            ],
            [
                ['data' => [['type' => 'test', 'attributes' => ['foo' => 'bar']]]],
                'The \'type\' property of the primary data object should match the requested resource',
                '/data/0/type',
                Constraint::CONFLICT,
                409
            ],
            [
                ['data' => [['type' => 'products', 'attributes' => null]]],
                'The \'attributes\' property should be an array',
                '/data/0/attributes'
            ],
            [
                ['data' => [['type' => 'products', 'attributes' => []]]],
                'The \'attributes\' property should not be empty',
                '/data/0/attributes'
            ],
            [
                ['data' => [['type' => 'products', 'attributes' => [1, 2, 3]]]],
                'The \'attributes\' property should be an associative array',
                '/data/0/attributes'
            ],
            [
                ['data' => [['type' => 'products', 'relationships' => null]]],
                'The \'relationships\' property should be an array',
                '/data/0/relationships'
            ],
            [
                ['data' => [['type' => 'products', 'relationships' => []]]],
                'The \'relationships\' property should not be empty',
                '/data/0/relationships'
            ],
            [
                ['data' => [['type' => 'products', 'relationships' => [1, 2, 3]]]],
                'The \'relationships\' property should be an associative array',
                '/data/0/relationships'
            ],
            [
                ['data' => [['type' => 'products', 'relationships' => ['test' => null]]]],
                'The relationship should have \'data\' property',
                '/data/0/relationships/test'
            ],
            [
                ['data' => [['type' => 'products', 'relationships' => ['test' => []]]]],
                'The relationship should have \'data\' property',
                '/data/0/relationships/test'
            ],
            [
                ['data' => [['type' => 'products', 'relationships' => ['test' => ['data' => ['id' => '1']]]]]],
                'The \'type\' property is required',
                '/data/0/relationships/test/data/type'
            ],
            [
                [
                    'data' => [
                        [
                            'type'          => 'products',
                            'relationships' => ['test' => ['data' => ['type' => 'products']]]
                        ]
                    ]
                ],
                'The \'id\' property is required',
                '/data/0/relationships/test/data/id'
            ],
            [
                [
                    'data' => [
                        [
                            'type'          => 'products',
                            'relationships' => ['test' => ['data' => [['id' => '1']]]]
                        ]
                    ]
                ],
                'The \'type\' property is required',
                '/data/0/relationships/test/data/0/type'
            ],
            [
                [
                    'data' => [
                        [
                            'type'          => 'products',
                            'relationships' => ['test' => ['data' => [['type' => 'products']]]]
                        ]
                    ]
                ],
                'The \'id\' property is required',
                '/data/0/relationships/test/data/0/id'
            ],
            [
                [
                    'data' => [
                        [
                            'type'          => 'products',
                            'relationships' => ['test' => ['data' => [['type' => 'products', 'id' => null]]]]
                        ]
                    ]
                ],
                'The \'id\' property should not be null',
                '/data/0/relationships/test/data/0/id'
            ],
            [
                [
                    'data' => [
                        [
                            'type'          => 'products',
                            'relationships' => ['test' => ['data' => [['type' => 'products', 'id' => '']]]]
                        ]
                    ]
                ],
                'The \'id\' property should not be blank',
                '/data/0/relationships/test/data/0/id'
            ],
            [
                [
                    'data' => [
                        [
                            'type'          => 'products',
                            'relationships' => ['test' => ['data' => [['type' => 'products', 'id' => ' ']]]]
                        ]
                    ]
                ],
                'The \'id\' property should not be blank',
                '/data/0/relationships/test/data/0/id'
            ],
            [
                [
                    'data' => [
                        [
                            'type'          => 'products',
                            'relationships' => ['test' => ['data' => [['type' => 'products', 'id' => 1]]]]
                        ]
                    ]
                ],
                'The \'id\' property should be a string',
                '/data/0/relationships/test/data/0/id'
            ]
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function invalidResourceObjectCollectionWithIncludedResourcesProvider()
    {
        return array_merge($this->invalidResourceObjectCollectionProvider(), [
            [
                ['data' => [['type' => 'products']], 'included' => null],
                'The \'included\' property should be an array',
                '/included'
            ],
            [
                ['data' => [['type' => 'products']], 'included' => []],
                'The \'included\' property should not be empty',
                '/included'
            ],
            [
                [
                    'data'     => [['type' => 'products']],
                    'included' => [
                        []
                    ]
                ],
                ['The \'type\' property is required', 'The \'id\' property is required'],
                ['/included/0/type', '/included/0/id']
            ],
            [
                [
                    'data'     => [['type' => 'products']],
                    'included' => [
                        ['type' => 'products']
                    ]
                ],
                'The \'id\' property is required',
                '/included/0/id'
            ],
            [
                [
                    'data'     => [['type' => 'products']],
                    'included' => [
                        ['type' => 'products', 'id' => null]
                    ]
                ],
                'The \'id\' property should not be null',
                '/included/0/id'
            ],
            [
                [
                    'data'     => [['type' => 'products']],
                    'included' => [
                        ['type' => 'products', 'id' => '']
                    ]
                ],
                'The \'id\' property should not be blank',
                '/included/0/id'
            ],
            [
                [
                    'data'     => [['type' => 'products']],
                    'included' => [
                        ['type' => 'products', 'id' => ' ']
                    ]
                ],
                'The \'id\' property should not be blank',
                '/included/0/id'
            ],
            [
                [
                    'data'     => [['type' => 'products']],
                    'included' => [
                        ['id' => '10']
                    ]
                ],
                'The \'type\' property is required',
                '/included/0/type'
            ],
            [
                [
                    'data'     => [['type' => 'products']],
                    'included' => [
                        ['type' => null, 'id' => '10']
                    ]
                ],
                'The \'type\' property should not be null',
                '/included/0/type'
            ],
            [
                [
                    'data'     => [['type' => 'products']],
                    'included' => [
                        ['type' => '', 'id' => '10']
                    ]
                ],
                'The \'type\' property should not be blank',
                '/included/0/type'
            ],
            [
                [
                    'data'     => [['type' => 'products']],
                    'included' => [
                        ['type' => ' ', 'id' => '10']
                    ]
                ],
                'The \'type\' property should not be blank',
                '/included/0/type'
            ]
        ]);
    }

    /**
     * @dataProvider invalidResourceObjectWithIdProvider
     */
    public function testInvalidResourceObjectWithId(
        $requestData,
        $expectedError,
        $pointer,
        $title = Constraint::REQUEST_DATA,
        $statusCode = 400
    ) {
        $errors = $this->validator->validateResourceObject(
            $requestData,
            false,
            Product::class,
            '1'
        );

        $this->assertValidationErrors((array)$expectedError, (array)$pointer, $title, $statusCode, $errors);
    }

    /**
     * @dataProvider invalidResourceObjectWithIdProvider
     */
    public function testInvalidResourceObjectWithIdWithIncludedResources(
        $requestData,
        $expectedError,
        $pointer,
        $title = Constraint::REQUEST_DATA,
        $statusCode = 400
    ) {
        $errors = $this->validator->validateResourceObject(
            $requestData,
            true,
            Product::class,
            '1'
        );

        $this->assertValidationErrors((array)$expectedError, (array)$pointer, $title, $statusCode, $errors);
    }

    public function invalidResourceObjectWithIdProvider()
    {
        return [
            [
                ['data' => ['type' => 'products', 'attributes' => ['foo' => 'bar']]],
                'The \'id\' property is required',
                '/data/id'
            ],
            [
                ['data' => ['type' => 'products', 'id' => '2', 'attributes' => ['foo' => 'bar']]],
                'The \'id\' property of the primary data object should match \'id\' parameter of the query sting',
                '/data/id',
                Constraint::CONFLICT,
                409
            ]
        ];
    }

    /**
     * @dataProvider invalidResourceObjectCollectionWithRequiredPrimaryResourceIdProvider
     */
    public function testInvalidResourceObjectCollectionWithRequiredPrimaryResourceId(
        $requestData,
        $expectedError,
        $pointer,
        $title = Constraint::REQUEST_DATA,
        $statusCode = 400
    ) {
        $errors = $this->validator->validateResourceObjectCollection(
            $requestData,
            false,
            Product::class,
            true
        );

        $this->assertValidationErrors((array)$expectedError, (array)$pointer, $title, $statusCode, $errors);
    }

    /**
     * @dataProvider invalidResourceObjectCollectionWithRequiredPrimaryResourceIdProvider
     */
    public function testInvalidResourceObjectCollectionWithRequiredPrimaryResourceIdWithIncludedResources(
        $requestData,
        $expectedError,
        $pointer,
        $title = Constraint::REQUEST_DATA,
        $statusCode = 400
    ) {
        $errors = $this->validator->validateResourceObjectCollection(
            $requestData,
            true,
            Product::class,
            true
        );

        $this->assertValidationErrors((array)$expectedError, (array)$pointer, $title, $statusCode, $errors);
    }

    public function invalidResourceObjectCollectionWithRequiredPrimaryResourceIdProvider()
    {
        return [
            [
                ['data' => [['type' => 'products', 'attributes' => ['foo' => 'bar']]]],
                'The \'id\' property is required',
                '/data/0/id'
            ]
        ];
    }

    public function testValidateResourceObjectWithNormalizedId()
    {
        $requestData = ['data' => ['type' => 'products', 'id' => '1', 'attributes' => ['test' => null]]];
        $normalizedId = 1;

        $errors = $this->validator->validateResourceObject(
            $requestData,
            false,
            Product::class,
            $normalizedId
        );

        self::assertEmpty($errors);
    }

    public function testValidateResourceObjectShouldNotContainIncludedSection()
    {
        $requestData = [
            'data'     => ['type' => 'products', 'id' => '1', 'attributes' => ['test' => null]],
            'included' => [['type' => 'products', 'id' => '10']]
        ];

        $errors = $this->validator->validateResourceObject(
            $requestData,
            false,
            Product::class
        );

        $this->assertValidationErrors(
            ['The \'included\' section should not exist'],
            ['/included'],
            Constraint::REQUEST_DATA,
            400,
            $errors
        );
    }

    public function testValidMetaObject()
    {
        $requestData = ['meta' => ['key' => 'value']];

        $errors = $this->validator->validateMetaObject($requestData);

        self::assertEmpty($errors);
    }

    /**
     * @dataProvider invalidMetaObjectProvider
     */
    public function testInvalidMetaObject($requestData, $expectedError, $pointer)
    {
        $errors = $this->validator->validateMetaObject($requestData);

        $this->assertValidationErrors(
            (array)$expectedError,
            (array)$pointer,
            Constraint::REQUEST_DATA,
            400,
            $errors
        );
    }

    public function invalidMetaObjectProvider()
    {
        return [
            [[], 'The primary meta object should exist', '/meta'],
            [['meta' => null], 'The primary meta object should not be empty', '/meta'],
            [['meta' => []], 'The primary meta object should not be empty', '/meta'],
            [['data' => ['type' => 'products']], 'The primary meta object should exist', '/meta'],
            [
                ['meta' => ['key' => 'value'], 'data' => []],
                'The \'data\' section should not exist',
                '/data'
            ],
            [
                ['meta' => ['key' => 'value'], 'included' => []],
                'The \'included\' section should not exist',
                '/included'
            ],
            [
                ['meta' => ['key' => 'value'], 'data' => [], 'included' => []],
                ['The \'data\' section should not exist', 'The \'included\' section should not exist'],
                ['/data', '/included']
            ]
        ];
    }
}
