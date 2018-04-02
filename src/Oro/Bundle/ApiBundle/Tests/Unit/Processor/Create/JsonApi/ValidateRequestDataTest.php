<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Create\JsonApi;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\Create\JsonApi\ValidateRequestData;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;

/**
 * This test case contains only cases for "create" action, for common tests see
 * @see \Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared\JsonApi\ValidateRequestDataTest
 */
class ValidateRequestDataTest extends FormProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|ValueNormalizer */
    private $valueNormalizer;

    /** @var ValidateRequestData */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->valueNormalizer = $this->createMock(ValueNormalizer::class);

        $this->processor = new ValidateRequestData($this->valueNormalizer);
    }

    /**
     * @dataProvider validRequestDataProvider
     */
    public function testProcessWithValidRequestData($requestData)
    {
        $metadata = new EntityMetadata();
        $metadata->setIdentifierFieldNames(['id']);

        $this->valueNormalizer->expects($this->any())
            ->method('normalizeValue')
            ->with('products')
            ->willReturn(Product::class);

        $this->context->setClassName(Product::class);
        $this->context->setMetadata($metadata);
        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasErrors());
    }

    public function validRequestDataProvider()
    {
        return [
            [
                ['data' => ['type' => 'products', 'attributes' => ['test' => null]]]
            ],
            [
                ['data' => ['id' => '1', 'type' => 'products', 'attributes' => ['test' => null]]]
            ],
            [
                ['data' => ['type' => 'products', 'relationships' => ['test' => ['data' => null]]]]
            ],
            [
                ['data' => ['type' => 'products', 'relationships' => ['test' => ['data' => []]]]]
            ],
            [
                ['data' => ['type' => 'products']]
            ]
        ];
    }

    /**
     * @dataProvider invalidRequestDataProvider
     */
    public function testProcessWithInvalidRequestData(
        $requestData,
        $expectedError,
        $pointer,
        $title = 'request data constraint',
        $statusCode = 400
    ) {
        $metadata = new EntityMetadata();
        $metadata->setIdentifierFieldNames(['id']);

        $this->valueNormalizer->expects(self::any())
            ->method('normalizeValue')
            ->with('products')
            ->willReturn(Product::class);

        $this->context->setClassName(Product::class);
        $this->context->setMetadata($metadata);
        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        $errors = $this->context->getErrors();

        $expectedError = (array)$expectedError;
        $pointer = (array)$pointer;
        self::assertCount(count($expectedError), $errors);
        foreach ($errors as $key => $error) {
            self::assertEquals($title, $error->getTitle());
            self::assertEquals($expectedError[$key], $error->getDetail());
            self::assertEquals($pointer[$key], $error->getSource()->getPointer());
            self::assertEquals($statusCode, $error->getStatusCode());
        }
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function invalidRequestDataProvider()
    {
        return [
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
            [['data' => ['attributes' => ['foo' => 'bar']]], 'The \'type\' property is required', '/data/type'],
            [
                ['data' => ['type' => 'test', 'attributes' => ['foo' => 'bar']]],
                'The \'type\' property of the primary data object should match the requested resource',
                '/data/type',
                'conflict constraint',
                409
            ]
        ];
    }
}
