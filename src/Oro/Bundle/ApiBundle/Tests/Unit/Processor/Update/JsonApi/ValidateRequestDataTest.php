<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Update\JsonApi;

use Oro\Bundle\ApiBundle\Processor\Update\JsonApi\ValidateRequestData;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;

/**
 * This test case contains only cases for "update" action, for common tests see
 * @see \Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared\JsonApi\ValidateRequestDataTest
 */
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
     * @dataProvider validRequestDataProvider
     */
    public function testProcessWithValidRequestData($requestData)
    {
        $this->valueNormalizer->expects($this->once())
            ->method('normalizeValue')
            ->with('products')
            ->willReturn('Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product');

        $this->context->setId('1');
        $this->context->setClassName('Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product');
        $this->context->setRequestData($requestData);

        $this->processor->process($this->context);
        $this->assertFalse($this->context->hasErrors());
    }

    public function validRequestDataProvider()
    {
        return [
            [
                ['data' => ['id' => '1', 'type' => 'products', 'attributes' => ['test' => null]]]
            ],
            [
                ['data' => ['id' => '1', 'type' => 'products', 'relationships' => ['test' => ['data' => null]]]]
            ],
            [
                ['data' => ['id' => '1', 'type' => 'products', 'relationships' => ['test' => ['data' => []]]]],
            ],
        ];
    }

    /**
     * @dataProvider invalidRequestDataProvider
     */
    public function testProcessWithInvalidRequestData($requestData, $expectedErrorString, $pointer)
    {
        $this->context->setId('1');
        $this->context->setClassName('Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product');
        $this->context->setRequestData($requestData);

        $this->valueNormalizer->expects($this->any())
            ->method('normalizeValue')
            ->with('products')
            ->willReturn('Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product');

        $this->processor->process($this->context);
        $errors = $this->context->getErrors();
        $this->assertCount(1, $errors);
        $expectedError = $errors[0];
        $this->assertEquals($expectedErrorString, $expectedError->getDetail());
        $this->assertEquals($pointer, $expectedError->getSource()->getPointer());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function invalidRequestDataProvider()
    {
        return [
            [['data' => ['attributes' => ['foo' => 'bar']]], 'The \'id\' property is required', '/data/id'],
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
                ['data' => ['id' => '10', 'type' => 'products', 'attributes' => ['foo' => 'bar']]],
                'The \'id\' property of the primary data object should match \'id\' parameter of the query sting',
                '/data/id',
            ],
            [
                ['data' => ['id' => '1', 'attributes' => ['foo' => 'bar']]],
                'The \'type\' property is required',
                '/data/type',
            ],
            [
                ['data' => ['id' => '1', 'type' => 'test', 'attributes' => ['foo' => 'bar']]],
                'The \'type\' property of the primary data object should match the requested resource',
                '/data/type',
            ],
            [
                ['data' => ['id' => '1', 'type' => 'products']],
                'The primary data object should contain \'attributes\' or \'relationships\' block',
                '/data',
            ],
        ];
    }
}
