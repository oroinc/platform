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
    /** @var ValueNormalizer|\PHPUnit\Framework\MockObject\MockObject */
    private $valueNormalizer;

    /** @var ValidateRequestData */
    private $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->valueNormalizer = $this->createMock(ValueNormalizer::class);

        $this->processor = new ValidateRequestData($this->valueNormalizer);
    }

    public function testProcessWhenRequestDataAlreadyValidated(): void
    {
        $this->context->setRequestData([]);
        $this->context->setProcessed(ValidateRequestData::OPERATION_NAME);
        $this->processor->process($this->context);
        self::assertFalse($this->context->hasErrors());
    }

    public function testProcessWithValidRequestDataForResourceWithoutIdentifier(): void
    {
        $requestData = [
            'meta' => ['foo' => 'bar']
        ];

        $metadata = new EntityMetadata('Test\Entity');

        $this->context->setRequestData($requestData);
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasErrors());
        self::assertTrue($this->context->isProcessed(ValidateRequestData::OPERATION_NAME));
    }

    public function testProcessWithEmptyRequestDataForResourceWithIdentifier(): void
    {
        $requestData = [];

        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);

        $this->context->setClassName(Product::class);
        $this->context->setRequestData($requestData);
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        $error = Error::createValidationError(
            Constraint::REQUEST_DATA,
            'The primary data object should exist'
        );
        $error->setSource(ErrorSource::createByPointer('/data'));
        self::assertEquals(
            [$error],
            $this->context->getErrors()
        );
        self::assertTrue($this->context->isProcessed(ValidateRequestData::OPERATION_NAME));
    }

    public function testProcessWithEmptyRequestDataForResourceWithoutIdentifier(): void
    {
        $requestData = [];

        $metadata = new EntityMetadata('Test\Entity');

        $this->context->setRequestData($requestData);
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        self::assertEquals([], $this->context->getErrors());
        self::assertTrue($this->context->isProcessed(ValidateRequestData::OPERATION_NAME));
    }

    public function testProcessWithValidRequestData(): void
    {
        $requestData = [
            'data' => ['type' => 'products', 'attributes' => ['foo' => 'bar']]
        ];

        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);

        $this->valueNormalizer->expects(self::any())
            ->method('normalizeValue')
            ->with('products')
            ->willReturn(Product::class);

        $this->context->setClassName(Product::class);
        $this->context->setRequestData($requestData);
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasErrors());
        self::assertTrue($this->context->isProcessed(ValidateRequestData::OPERATION_NAME));
    }

    public function testProcessWithInvalidRequestData(): void
    {
        $requestData = [
            'data' => ['type' => 'test', 'attributes' => ['foo' => 'bar']]
        ];

        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);

        $this->valueNormalizer->expects(self::any())
            ->method('normalizeValue')
            ->with('test')
            ->willReturn('Test\Entity');

        $this->context->setClassName(Product::class);
        $this->context->setRequestData($requestData);
        $this->context->setMetadata($metadata);
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
