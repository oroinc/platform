<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\ChangeSubresource\JsonApi;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeSubresource\JsonApi\ValidateRequestData;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\ChangeSubresourceProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class ValidateRequestDataTest extends ChangeSubresourceProcessorTestCase
{
    private ValidateRequestData $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new ValidateRequestData();
    }

    public function testProcessWhenRequestDataAlreadyValidated(): void
    {
        $this->context->setRequestData([]);
        $this->context->setProcessed(ValidateRequestData::OPERATION_NAME);
        $this->processor->process($this->context);
        self::assertFalse($this->context->hasErrors());
    }

    public function testProcessWithValidRequestDataForAssociationWithoutIdentifier(): void
    {
        $requestData = [
            'meta' => ['foo' => 'bar']
        ];

        $metadata = new EntityMetadata('Test\Entity');

        $this->context->setRequestData($requestData);
        $this->context->setMetadata($metadata);
        $this->context->setIsCollection(false);
        $this->context->setParentConfig(new EntityDefinitionConfig());
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasErrors());
        self::assertTrue($this->context->isProcessed(ValidateRequestData::OPERATION_NAME));
    }

    public function testProcessWithEmptyRequestDataForAssociationWithIdentifier(): void
    {
        $requestData = [];

        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);

        $this->context->setRequestData($requestData);
        $this->context->setMetadata($metadata);
        $this->context->setIsCollection(false);
        $this->context->setParentConfig(new EntityDefinitionConfig());
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

    public function testProcessWithEmptyRequestDataForAssociationWithoutIdentifier(): void
    {
        $requestData = [];

        $metadata = new EntityMetadata('Test\Entity');

        $this->context->setRequestData($requestData);
        $this->context->setMetadata($metadata);
        $this->context->setIsCollection(false);
        $this->context->setParentConfig(new EntityDefinitionConfig());
        $this->processor->process($this->context);

        self::assertEquals([], $this->context->getErrors());
        self::assertTrue($this->context->isProcessed(ValidateRequestData::OPERATION_NAME));
    }

    public function testProcessWithValidRequestDataForToOneAssociation(): void
    {
        $requestData = [
            'data' => ['type' => 'products', 'id' => '123', 'attributes' => ['foo' => 'bar']]
        ];

        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);

        $this->context->setRequestData($requestData);
        $this->context->setMetadata($metadata);
        $this->context->setIsCollection(false);
        $this->context->setParentConfig(new EntityDefinitionConfig());
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasErrors());
        self::assertTrue($this->context->isProcessed(ValidateRequestData::OPERATION_NAME));
    }

    public function testProcessWithValidRequestDataForToManyAssociation(): void
    {
        $requestData = [
            'data' => [
                ['type' => 'products', 'id' => '123', 'attributes' => ['foo' => 'bar']]
            ]
        ];

        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);

        $this->context->setRequestData($requestData);
        $this->context->setMetadata($metadata);
        $this->context->setIsCollection(true);
        $this->context->setParentConfig(new EntityDefinitionConfig());
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasErrors());
        self::assertTrue($this->context->isProcessed(ValidateRequestData::OPERATION_NAME));
    }

    public function testProcessWithInvalidRequestDataForToOneAssociation(): void
    {
        $requestData = ['data' => null];

        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);

        $this->context->setRequestData($requestData);
        $this->context->setMetadata($metadata);
        $this->context->setIsCollection(false);
        $this->context->setParentConfig(new EntityDefinitionConfig());
        $this->processor->process($this->context);

        $error = Error::createValidationError(
            Constraint::REQUEST_DATA,
            'The primary data object should not be empty'
        );
        $error->setSource(ErrorSource::createByPointer('/data'));
        self::assertEquals(
            [$error],
            $this->context->getErrors()
        );
        self::assertTrue($this->context->isProcessed(ValidateRequestData::OPERATION_NAME));
    }

    public function testProcessWithInvalidRequestDataForToManyAssociation(): void
    {
        $requestData = ['data' => null];

        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);

        $this->context->setRequestData($requestData);
        $this->context->setMetadata($metadata);
        $this->context->setIsCollection(true);
        $this->context->setParentConfig(new EntityDefinitionConfig());
        $this->processor->process($this->context);

        $error = Error::createValidationError(
            Constraint::REQUEST_DATA,
            'The primary data object collection should not be empty'
        );
        $error->setSource(ErrorSource::createByPointer('/data'));
        self::assertEquals(
            [$error],
            $this->context->getErrors()
        );
        self::assertTrue($this->context->isProcessed(ValidateRequestData::OPERATION_NAME));
    }

    public function testProcessWithValidRequestDataWhenRequestEntityNotEqualsToResponseEntity(): void
    {
        $requestData = [
            'meta' => ['foo' => 'bar']
        ];

        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->set(ConfigUtil::REQUEST_TARGET_CLASS, 'Test\RequestEntity');

        $metadata = new EntityMetadata('Test\RequestEntity');

        $this->context->setRequestData($requestData);
        $this->context->setRequestMetadata($metadata);
        $this->context->setIsCollection(false);
        $this->context->setParentConfig($parentConfig);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasErrors());
        self::assertTrue($this->context->isProcessed(ValidateRequestData::OPERATION_NAME));
    }
}
