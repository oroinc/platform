<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Create\JsonApi;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Create\JsonApi\ValidateRequestData;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Create\CreateProcessorTestCase;

class ValidateRequestDataTest extends CreateProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ValueNormalizer */
    private $valueNormalizer;

    /** @var ValidateRequestData */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->valueNormalizer = $this->createMock(ValueNormalizer::class);

        $this->processor = new ValidateRequestData($this->valueNormalizer);
    }

    public function testProcessWhenRequestDataAlreadyValidated()
    {
        $this->context->setRequestData([]);
        $this->context->setProcessed(ValidateRequestData::OPERATION_NAME);
        $this->processor->process($this->context);
        self::assertFalse($this->context->hasErrors());
    }

    public function testProcessWithValidRequestDataForResourceWithoutIdentifier()
    {
        $requestData = [
            'meta' => ['foo' => 'bar']
        ];

        $metadata = new EntityMetadata();

        $this->context->setRequestData($requestData);
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasErrors());
        self::assertTrue($this->context->isProcessed(ValidateRequestData::OPERATION_NAME));
    }

    public function testProcessWithInvalidRequestDataForResourceWithoutIdentifier()
    {
        $requestData = [];

        $metadata = new EntityMetadata();

        $this->context->setRequestData($requestData);
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        $error = Error::createValidationError(
            Constraint::REQUEST_DATA,
            'The primary meta object should exist'
        );
        $error->setSource(ErrorSource::createByPointer('/meta'));
        self::assertEquals(
            [$error],
            $this->context->getErrors()
        );
        self::assertTrue($this->context->isProcessed(ValidateRequestData::OPERATION_NAME));
    }

    public function testProcessWithValidRequestData()
    {
        $requestData = [
            'data' => ['type' => 'products', 'attributes' => ['foo' => 'bar']]
        ];

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

        self::assertFalse($this->context->hasErrors());
        self::assertTrue($this->context->isProcessed(ValidateRequestData::OPERATION_NAME));
    }

    public function testProcessWithInvalidRequestData()
    {
        $requestData = [
            'data' => ['type' => 'test', 'attributes' => ['foo' => 'bar']]
        ];

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

        $error = Error::createConflictValidationError(
            'The \'type\' property of the primary data object should match the requested resource'
        );
        $error->setSource(ErrorSource::createByPointer('/data/type'));
        self::assertEquals(
            [$error],
            $this->context->getErrors()
        );
        self::assertTrue($this->context->isProcessed(ValidateRequestData::OPERATION_NAME));
    }
}
