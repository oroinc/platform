<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Shared\ValidateEntityIdExists;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\GetProcessorTestCase;

class ValidateEntityIdExistsTest extends GetProcessorTestCase
{
    /** @var ValidateEntityIdExists */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new ValidateEntityIdExists();
    }

    public function testProcess()
    {
        $this->context->setId(123);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasErrors());
    }

    public function testProcessWhenNoId()
    {
        $metadata = new EntityMetadata();
        $metadata->setIdentifierFieldNames(['id']);

        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        self::assertEquals(
            [
                Error::createValidationError(
                    'entity identifier constraint',
                    'The identifier of an entity must be set in the context.'
                )
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWhenNoIdAndEntityDoesNotHaveIdentifierFields()
    {
        $metadata = new EntityMetadata();

        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasErrors());
    }
}
