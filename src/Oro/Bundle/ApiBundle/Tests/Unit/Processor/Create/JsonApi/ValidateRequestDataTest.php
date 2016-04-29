<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Create\JsonApi;

use Oro\Bundle\ApiBundle\Processor\Create\JsonApi\ValidateRequestData;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;

class ValidateRequestDataTest extends FormProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $valueNormalizer;

    /** @var ValidateRequestData */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->valueNormalizer = $this->getMockBuilder('Oro\Bundle\ApiBundle\Request\ValueNormalizer')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new ValidateRequestData($this->valueNormalizer);
    }

    /**
     * @dataProvider requestDataProvider
     */
    public function testProcess($requestData, $expectedErrorString, $pointer, $normalizedValue = '')
    {
        $this->context->setClassName('Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product');
        $this->context->setRequestData($requestData);

        if ($normalizedValue) {
            $this->valueNormalizer->expects($this->once())
                ->method('normalizeValue')
                ->willReturn($normalizedValue);
        }

        $this->processor->process($this->context);

        $errors = $this->context->getErrors();
        $this->assertCount(1, $errors);
        $error = $errors[0];
        $this->assertEquals(400, $error->getStatusCode());
        $this->assertEquals('request data constraint', $error->getTitle());
        $this->assertEquals($expectedErrorString, $error->getDetail());
        $this->assertEquals($pointer, $error->getSource()->getPointer());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function requestDataProvider()
    {
        return [
            [[], 'The primary data object should exist', '/data'],
            [['data' => null], 'The primary data object should not be empty', '/data'],
            [['data' => []], 'The primary data object should not be empty', '/data'],
            [['data' => ['attributes' => ['foo' => 'bar']]], 'The \'type\' property is required', '/data/type'],
            [
                ['data' => ['type' => 'test', 'attributes' => ['foo' => 'bar']]],
                'The \'type\' property of the primary data object should match the requested resource',
                '/data/type',
                'test'
            ],
            [
                ['data' => ['type' => 'test']],
                'The primary data object should contain \'attributes\' or \'relationships\' block',
                '/data',
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product'
            ],
            [
                ['data' => ['type' => 'test']],
                'The primary data object should contain \'attributes\' or \'relationships\' block',
                '/data',
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product'
            ],
            [
                ['data' => ['type' => 'test', 'attributes' => null]],
                'The \'attributes\' property should be an array',
                '/data/attributes',
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product'
            ],
            [
                ['data' => ['type' => 'test', 'attributes' => []]],
                'The \'attributes\' property should not be empty',
                '/data/attributes',
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product'
            ],
            [
                ['data' => ['type' => 'test', 'attributes' => [1, 2, 3]]],
                'The \'attributes\' property should be an associative array',
                '/data/attributes',
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product'
            ],
            [
                ['data' => ['type' => 'test', 'relationships' => null]],
                'The \'relationships\' property should be an array',
                '/data/relationships',
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product'
            ],
            [
                ['data' => ['type' => 'test', 'relationships' => []]],
                'The \'relationships\' property should not be empty',
                '/data/relationships',
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product'
            ],
            [
                ['data' => ['type' => 'test', 'relationships' => [1, 2, 3]]],
                'The \'relationships\' property should be an associative array',
                '/data/relationships',
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product'
            ],
            [
                ['data' => ['type' => 'test', 'relationships' => ['test' => null]]],
                'The relationship should have \'data\' property',
                '/data/relationships/test',
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product'
            ],
            [
                ['data' => ['type' => 'test', 'relationships' => ['test' => []]]],
                'The relationship should have \'data\' property',
                '/data/relationships/test',
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product'
            ],
            [
                ['data' => ['type' => 'test', 'relationships' => ['test' => ['data' => null]]]],
                'The \'data\' property should be an array',
                '/data/relationships/test/data',
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product'
            ],
            [
                ['data' => ['type' => 'test', 'relationships' => ['test' => ['data' => []]]]],
                'The \'data\' property should not be empty',
                '/data/relationships/test/data',
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product'
            ],
            [
                ['data' => ['type' => 'test', 'relationships' => ['test' => ['data' => []]]]],
                'The \'data\' property should not be empty',
                '/data/relationships/test/data',
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product'
            ],
            [
                ['data' => ['type' => 'test', 'relationships' => ['test' => ['data' => ['id' => '2']]]]],
                'The \'type\' property is required',
                '/data/relationships/test/data/type',
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product'
            ],
            [
                [
                    'data' =>
                        [
                            'type'          => 'test',
                            'relationships' => ['test' => ['data' => ['type' => 'test']]]
                        ]
                ],
                'The \'id\' property is required',
                '/data/relationships/test/data/id',
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product'
            ],
            [
                [
                    'data' =>
                        [
                            'type'          => 'test',
                            'relationships' => ['test' => ['data' => [['id' => '2']]]]
                        ]
                ],
                'The \'type\' property is required',
                '/data/relationships/test/data/0/type',
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product'
            ],
            [
                [
                    'data' =>
                        [
                            'type'          => 'test',
                            'relationships' => ['test' => ['data' => [['type' => 'test']]]]
                        ]
                ],
                'The \'id\' property is required',
                '/data/relationships/test/data/0/id',
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product'
            ]
        ];
    }
}
