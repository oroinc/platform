<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared\JsonApi;

use Oro\Bundle\ApiBundle\Processor\Shared\JsonApi\ValidateRequestData;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;

class ValidateRequestDataTest extends FormProcessorTestCase
{
    /** @var ValidateRequestData */
    protected $processor;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $valueNormalizer;

    protected function setUp()
    {
        parent::setUp();

        $this->valueNormalizer = $this->getMockBuilder('Oro\Bundle\ApiBundle\Request\ValueNormalizer')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = $this->getProcessor();
    }

    /**
     * @dataProvider requestDataProvider
     */
    public function testProcess($requestData, $expectedErrorString, $pointer, $normalizeValue = '')
    {
        $this->context->setId('23');
        $this->context->setClassName('Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product');
        $this->context->setRequestData($requestData);

        if ($normalizeValue) {
            $this->valueNormalizer->expects($this->once())
                ->method('normalizeValue')
                ->willReturn($normalizeValue);
        }

        $this->processor->process($this->context);
        $errors = $this->context->getErrors();
        $this->assertEquals(1, count($errors));
        $expectedError = $errors[0];
        $this->assertEquals($expectedErrorString, $expectedError->getDetail());
        $this->assertEquals($pointer, $expectedError->getSource()->getPointer());
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
            [['data' => ['attributes' => ['foo' => 'bar']]], 'The \'id\' parameter is required', '/data/id'],
            [
                ['data' => ['id' => '1', 'attributes' => ['foo' => 'bar']]],
                'The \'type\' parameter is required',
                '/data/type'
            ],
            [
                ['data' => ['id' => '23', 'type' => 'test', 'attributes' => ['foo' => 'bar']]],
                'The \'type\' parameters in request data and query sting should match each other',
                '/data/type',
                'test'
            ],
            [
                ['data' => ['id' => '32', 'type' => 'test', 'attributes' => ['foo' => 'bar']]],
                'The \'id\' parameters in request data and query sting should match each other',
                '/data/id',
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product'
            ],
            [
                ['data' => ['id' => '23', 'type' => 'test']],
                'The primary data object should contain \'attributes\' or \'relationships\' block',
                '/data',
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product'
            ],
            [
                ['data' => ['id' => '23', 'type' => 'test']],
                'The primary data object should contain \'attributes\' or \'relationships\' block',
                '/data',
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product'
            ],
            [
                ['data' => ['id' => '23', 'type' => 'test', 'attributes' => null]],
                'The \'attributes\' parameter should be an array',
                '/data/attributes',
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product'
            ],
            [
                ['data' => ['id' => '23', 'type' => 'test', 'attributes' => []]],
                'The \'attributes\' parameter should not be empty',
                '/data/attributes',
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product'
            ],
            [
                ['data' => ['id' => '23', 'type' => 'test', 'attributes' => [1,2,3]]],
                'The \'attributes\' parameter should be an associative array',
                '/data/attributes',
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product'
            ],
            [
                ['data' => ['id' => '23', 'type' => 'test', 'relationships' => null]],
                'The \'relationships\' parameter should be an array',
                '/data/relationships',
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product'
            ],
            [
                ['data' => ['id' => '23', 'type' => 'test', 'relationships' => []]],
                'The \'relationships\' parameter should not be empty',
                '/data/relationships',
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product'
            ],
            [
                ['data' => ['id' => '23', 'type' => 'test', 'relationships' => ['test' => null]]],
                'Data object have no data',
                '/data/relationships/test/data',
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product'
            ],
            [
                ['data' => ['id' => '23', 'type' => 'test', 'relationships' => ['test' => ['data' => null]]]],
                'The primary data object should not be empty',
                '/data/relationships/test/data',
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product'
            ],
            [
                ['data' => ['id' => '23', 'type' => 'test', 'relationships' => ['test' => ['data' => []]]]],
                'The primary data object should not be empty',
                '/data/relationships/test/data',
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product'
            ],
            [
                ['data' => ['id' => '23', 'type' => 'test', 'relationships' => ['test' => ['data' => []]]]],
                'The primary data object should not be empty',
                '/data/relationships/test/data',
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product'
            ],
            [
                ['data' => ['id' => '23', 'type' => 'test', 'relationships' => ['test' => ['data' => ['id' => '2']]]]],
                'The \'type\' parameter is required',
                '/data/relationships/test/data/type',
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product'
            ],
            [
                ['data' =>
                     [
                         'id' => '23',
                         'type' => 'test',
                         'relationships' => ['test' => ['data' => ['type' => 'test']]]
                     ]
                ],
                'The \'id\' parameter is required',
                '/data/relationships/test/data/id',
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product'
            ],
            [
                ['data' =>
                     [
                         'id' => '23',
                         'type' => 'test',
                         'relationships' => ['test' => ['data' => [['id' => '2']]]]
                     ]
                ],
                'The \'type\' parameter is required',
                '/data/relationships/test/data/0/type',
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product'
            ],
            [
                ['data' =>
                     [
                         'id' => '23',
                         'type' => 'test',
                         'relationships' => ['test' => ['data' => [['type' => 'test']]]]
                     ]
                ],
                'The \'id\' parameter is required',
                '/data/relationships/test/data/0/id',
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product'
            ]
        ];
    }

    /**
     * @return ValidateRequestData
     */
    protected function getProcessor()
    {
        return new ValidateRequestData($this->valueNormalizer);
    }
}
