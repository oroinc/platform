<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared\JsonApi;

use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;

class ValidateRequestDataTest extends FormProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $valueNormalizer;

    /** @var ValidateRequestDataStub */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->valueNormalizer = $this->getMockBuilder('Oro\Bundle\ApiBundle\Request\ValueNormalizer')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new ValidateRequestDataStub($this->valueNormalizer);
    }

    /**
     * @dataProvider validRequestDataProvider
     */
    public function testProcessWithValidRequestData($requestData)
    {
        $this->valueNormalizer->expects($this->any())
            ->method('normalizeValue')
            ->with('products')
            ->willReturn('Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product');

        $this->context->setClassName('Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product');
        $this->context->setRequestData($requestData);

        $this->processor->process($this->context);
        $this->assertFalse($this->context->hasErrors());
    }

    public function validRequestDataProvider()
    {
        return [
            [
                ['data' => ['type' => 'products']]
            ],
            [
                ['data' => ['type' => 'products', 'attributes' => ['test' => null]]]
            ],
            [
                ['data' => ['type' => 'products', 'relationships' => ['test' => ['data' => null]]]]
            ],
            [
                ['data' => ['type' => 'products', 'relationships' => ['test' => ['data' => []]]]]
            ],
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
            ],
        ];
    }

    /**
     * @dataProvider invalidRequestDataProvider
     */
    public function testProcessWithInvalidRequestData($requestData, $expectedError, $pointer)
    {
        $this->context->setClassName('Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product');
        $this->context->setRequestData($requestData);

        $this->valueNormalizer->expects($this->any())
            ->method('normalizeValue')
            ->with('products')
            ->willReturn('Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product');

        $this->processor->process($this->context);

        $errors = $this->context->getErrors();

        $expectedError = (array)$expectedError;
        $pointer = (array)$pointer;
        $this->assertCount(count($expectedError), $errors);
        foreach ($errors as $key => $error) {
            $this->assertEquals('request data constraint', $error->getTitle());
            $this->assertEquals($expectedError[$key], $error->getDetail());
            $this->assertEquals($pointer[$key], $error->getSource()->getPointer());
        }
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function invalidRequestDataProvider()
    {
        return [
            [[], 'The primary data object should exist', '/data'],
            [['data' => null], 'The primary data object should not be empty', '/data'],
            [['data' => []], 'The primary data object should not be empty', '/data'],
            [
                ['data' => ['type' => 'products', 'attributes' => null]],
                'The \'attributes\' property should be an array',
                '/data/attributes',
            ],
            [
                ['data' => ['type' => 'products', 'attributes' => []]],
                'The \'attributes\' property should not be empty',
                '/data/attributes',
            ],
            [
                ['data' => ['type' => 'products', 'attributes' => [1, 2, 3]]],
                'The \'attributes\' property should be an associative array',
                '/data/attributes',
            ],
            [
                ['data' => ['type' => 'products', 'relationships' => null]],
                'The \'relationships\' property should be an array',
                '/data/relationships',
            ],
            [
                ['data' => ['type' => 'products', 'relationships' => []]],
                'The \'relationships\' property should not be empty',
                '/data/relationships',
            ],
            [
                ['data' => ['type' => 'products', 'relationships' => [1, 2, 3]]],
                'The \'relationships\' property should be an associative array',
                '/data/relationships',
            ],
            [
                ['data' => ['type' => 'products', 'relationships' => ['test' => null]]],
                'The relationship should have \'data\' property',
                '/data/relationships/test',
            ],
            [
                ['data' => ['type' => 'products', 'relationships' => ['test' => []]]],
                'The relationship should have \'data\' property',
                '/data/relationships/test',
            ],
            [
                ['data' => ['type' => 'products', 'relationships' => ['test' => ['data' => ['id' => '1']]]]],
                'The \'type\' property is required',
                '/data/relationships/test/data/type',
            ],
            [
                [
                    'data' => [
                        'type'          => 'products',
                        'relationships' => ['test' => ['data' => ['type' => 'products']]]
                    ]
                ],
                'The \'id\' property is required',
                '/data/relationships/test/data/id',
            ],
            [
                [
                    'data' => [
                        'type'          => 'products',
                        'relationships' => ['test' => ['data' => [['id' => '1']]]]
                    ]
                ],
                'The \'type\' property is required',
                '/data/relationships/test/data/0/type',
            ],
            [
                [
                    'data' => [
                        'type'          => 'products',
                        'relationships' => ['test' => ['data' => [['type' => 'products']]]]
                    ]
                ],
                'The \'id\' property is required',
                '/data/relationships/test/data/0/id',
            ],
            [
                [
                    'data' => [
                        'type'          => 'products',
                        'relationships' => ['test' => ['data' => [['type' => 'products', 'id' => null]]]]
                    ]
                ],
                'The \'id\' property should not be null',
                '/data/relationships/test/data/0/id',
            ],
            [
                [
                    'data' => [
                        'type'          => 'products',
                        'relationships' => ['test' => ['data' => [['type' => 'products', 'id' => '']]]]
                    ]
                ],
                'The \'id\' property should not be blank',
                '/data/relationships/test/data/0/id',
            ],
            [
                [
                    'data' => [
                        'type'          => 'products',
                        'relationships' => ['test' => ['data' => [['type' => 'products', 'id' => ' ']]]]
                    ]
                ],
                'The \'id\' property should not be blank',
                '/data/relationships/test/data/0/id',
            ],
            [
                [
                    'data' => [
                        'type'          => 'products',
                        'relationships' => ['test' => ['data' => [['type' => 'products', 'id' => 1]]]]
                    ]
                ],
                'The \'id\' property should be a string',
                '/data/relationships/test/data/0/id',
            ],
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
            ],
        ];
    }
}
