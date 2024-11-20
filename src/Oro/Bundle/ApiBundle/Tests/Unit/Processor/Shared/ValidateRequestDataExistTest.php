<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Shared\ValidateRequestDataExist;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;

class ValidateRequestDataExistTest extends FormProcessorTestCase
{
    private ValidateRequestDataExist $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->processor = new ValidateRequestDataExist();
    }

    public function testProcessOnNotExistingDataForEntityWithIdentifierField(): void
    {
        $metadata = new EntityMetadata('Test\Class');
        $metadata->setIdentifierFieldNames(['id']);

        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);
        self::assertEquals(
            [Error::createValidationError('request data constraint', 'The request data should not be empty.')],
            $this->context->getErrors()
        );
    }

    public function testProcessOnNotExistingDataForEntityWithoutIdentifierField(): void
    {
        $metadata = new EntityMetadata('Test\Class');

        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);
        self::assertEquals([], $this->context->getErrors());
    }

    public function testProcessOnNotExistingDataAndNoRequestMetadata(): void
    {
        $this->context->setMetadata(null);
        $this->processor->process($this->context);
        self::assertEquals([], $this->context->getErrors());
    }

    public function testProcessOnEmptyDataForEntityWithIdentifierField(): void
    {
        $metadata = new EntityMetadata('Test\Class');
        $metadata->setIdentifierFieldNames(['id']);

        $this->context->setMetadata($metadata);
        $this->context->setRequestData([]);
        $this->processor->process($this->context);
        self::assertEquals(
            [Error::createValidationError('request data constraint', 'The request data should not be empty.')],
            $this->context->getErrors()
        );
    }

    public function testProcessOnEmptyDataForEntityWithoutIdentifierField(): void
    {
        $metadata = new EntityMetadata('Test\Class');

        $this->context->setMetadata($metadata);
        $this->context->setRequestData([]);
        $this->processor->process($this->context);
        self::assertEquals([], $this->context->getErrors());
    }

    public function testProcessOnEmptyDataAndNoRequestMetadata(): void
    {
        $this->context->setMetadata(null);
        $this->context->setRequestData([]);
        $this->processor->process($this->context);
        self::assertEquals([], $this->context->getErrors());
    }

    public function testProcessWithData(): void
    {
        $metadata = new EntityMetadata('Test\Class');

        $this->context->setMetadata($metadata);
        $this->context->setRequestData(['a' => 'b']);
        $this->processor->process($this->context);
        self::assertFalse($this->context->hasErrors());
    }
}
