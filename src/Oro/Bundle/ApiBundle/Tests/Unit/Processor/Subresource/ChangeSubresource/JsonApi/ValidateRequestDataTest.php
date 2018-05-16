<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\ChangeSubresource\JsonApi;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeSubresource\JsonApi\ValidateRequestData;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\ChangeSubresourceProcessorTestCase;

class ValidateRequestDataTest extends ChangeSubresourceProcessorTestCase
{
    /** @var ValidateRequestData */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new ValidateRequestData();
    }

    public function testProcessWhenRequestDataAlreadyValidated()
    {
        $this->context->setRequestData([]);
        $this->context->setProcessed(ValidateRequestData::OPERATION_NAME);
        $this->processor->process($this->context);
        self::assertFalse($this->context->hasErrors());
    }

    public function testProcessWithValidRequestDataForAssociationWithoutIdentifier()
    {
        $requestData = [
            'meta' => ['foo' => 'bar']
        ];

        $metadata = new EntityMetadata();

        $this->context->setRequestData($requestData);
        $this->context->setMetadata($metadata);
        $this->context->setIsCollection(false);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasErrors());
        self::assertTrue($this->context->isProcessed(ValidateRequestData::OPERATION_NAME));
    }

    public function testProcessWithInvalidRequestDataForAssociationWithoutIdentifier()
    {
        $requestData = [];

        $metadata = new EntityMetadata();

        $this->context->setRequestData($requestData);
        $this->context->setMetadata($metadata);
        $this->context->setIsCollection(false);
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

    public function testProcessWithValidRequestDataForToOneAssociation()
    {
        $requestData = [
            'data' => ['type' => 'products', 'id' => '123', 'attributes' => ['foo' => 'bar']]
        ];

        $metadata = new EntityMetadata();
        $metadata->setIdentifierFieldNames(['id']);

        $this->context->setRequestData($requestData);
        $this->context->setMetadata($metadata);
        $this->context->setIsCollection(false);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasErrors());
        self::assertTrue($this->context->isProcessed(ValidateRequestData::OPERATION_NAME));
    }

    public function testProcessWithValidRequestDataForToManyAssociation()
    {
        $requestData = [
            'data' => [
                ['type' => 'products', 'id' => '123', 'attributes' => ['foo' => 'bar']]
            ]
        ];

        $metadata = new EntityMetadata();
        $metadata->setIdentifierFieldNames(['id']);

        $this->context->setRequestData($requestData);
        $this->context->setMetadata($metadata);
        $this->context->setIsCollection(true);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasErrors());
        self::assertTrue($this->context->isProcessed(ValidateRequestData::OPERATION_NAME));
    }

    public function testProcessWithInvalidRequestDataForToOneAssociation()
    {
        $requestData = ['data' => null];

        $metadata = new EntityMetadata();
        $metadata->setIdentifierFieldNames(['id']);

        $this->context->setRequestData($requestData);
        $this->context->setMetadata($metadata);
        $this->context->setIsCollection(false);
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

    public function testProcessWithInvalidRequestDataForToManyAssociation()
    {
        $requestData = ['data' => null];

        $metadata = new EntityMetadata();
        $metadata->setIdentifierFieldNames(['id']);

        $this->context->setRequestData($requestData);
        $this->context->setMetadata($metadata);
        $this->context->setIsCollection(true);
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
}
