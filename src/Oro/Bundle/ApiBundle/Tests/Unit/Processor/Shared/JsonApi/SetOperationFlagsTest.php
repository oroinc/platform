<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared\JsonApi;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Shared\JsonApi\SetOperationFlags;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;

class SetOperationFlagsTest extends FormProcessorTestCase
{
    private SetOperationFlags $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->processor = new SetOperationFlags();
    }

    public function testProcessForEmptyRequestData(): void
    {
        $this->context->setRequestData([]);
        $this->processor->process($this->context);

        self::assertFalse($this->context->has(SetOperationFlags::UPDATE_FLAG));
        self::assertFalse($this->context->has(SetOperationFlags::UPSERT_FLAG));
        self::assertFalse($this->context->has(SetOperationFlags::VALIDATE_FLAG));
    }

    public function testProcessWhenRequestDataDoesNotContainMeta(): void
    {
        $this->context->setRequestData(['data' => ['type' => 'test']]);
        $this->processor->process($this->context);

        self::assertFalse($this->context->has(SetOperationFlags::UPDATE_FLAG));
        self::assertFalse($this->context->has(SetOperationFlags::UPSERT_FLAG));
        self::assertFalse($this->context->has(SetOperationFlags::VALIDATE_FLAG));
    }

    public function testProcessWhenRequestDataContainsNotArrayMeta(): void
    {
        $this->context->setRequestData(['data' => ['type' => 'test', 'meta' => 'test']]);
        $this->processor->process($this->context);

        self::assertFalse($this->context->has(SetOperationFlags::UPDATE_FLAG));
        self::assertFalse($this->context->has(SetOperationFlags::UPSERT_FLAG));
        self::assertFalse($this->context->has(SetOperationFlags::VALIDATE_FLAG));
    }

    public function testProcessWhenRequestDataContainsEmptyMeta(): void
    {
        $this->context->setRequestData(['data' => ['type' => 'test', 'meta' => []]]);
        $this->processor->process($this->context);

        self::assertFalse($this->context->has(SetOperationFlags::UPDATE_FLAG));
        self::assertFalse($this->context->has(SetOperationFlags::UPSERT_FLAG));
        self::assertFalse($this->context->has(SetOperationFlags::VALIDATE_FLAG));
    }

    public function testProcessWhenRequestDataContainsUpdateFlagInMeta(): void
    {
        $this->context->setRequestData(['data' => ['type' => 'test', 'meta' => ['update' => true]]]);
        $this->processor->process($this->context);

        self::assertTrue($this->context->get(SetOperationFlags::UPDATE_FLAG));
        self::assertFalse($this->context->has(SetOperationFlags::UPSERT_FLAG));
        self::assertFalse($this->context->has(SetOperationFlags::VALIDATE_FLAG));
    }

    public function testProcessWhenRequestDataContainsUpsertFlagInMeta(): void
    {
        $this->context->setRequestData(['data' => ['type' => 'test', 'meta' => ['upsert' => true]]]);
        $this->processor->process($this->context);

        self::assertFalse($this->context->has(SetOperationFlags::UPDATE_FLAG));
        self::assertTrue($this->context->get(SetOperationFlags::UPSERT_FLAG));
        self::assertFalse($this->context->has(SetOperationFlags::VALIDATE_FLAG));
    }

    public function testProcessWhenRequestDataContainsValidateFlagInMeta(): void
    {
        $this->context->setRequestData(['data' => ['type' => 'test', 'meta' => ['validate' => true]]]);
        $this->processor->process($this->context);

        self::assertFalse($this->context->has(SetOperationFlags::UPDATE_FLAG));
        self::assertFalse($this->context->has(SetOperationFlags::UPSERT_FLAG));
        self::assertTrue($this->context->get(SetOperationFlags::VALIDATE_FLAG));
    }

    public function testProcessWhenRequestDataContainsInvalidUpdateFlagInMeta(): void
    {
        $this->context->setRequestData(['data' => ['type' => 'test', 'meta' => ['update' => 'test']]]);
        $this->processor->process($this->context);

        self::assertFalse($this->context->has(SetOperationFlags::UPDATE_FLAG));
        self::assertFalse($this->context->has(SetOperationFlags::UPSERT_FLAG));
        self::assertFalse($this->context->has(SetOperationFlags::VALIDATE_FLAG));
        self::assertEquals(
            [
                Error::createValidationError(
                    Constraint::VALUE,
                    'This value should be a boolean.'
                )->setSource(ErrorSource::createByPointer('/meta/update'))
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWhenRequestDataContainsInvalidUpsertFlagInMeta(): void
    {
        $this->context->setRequestData(['data' => ['type' => 'test', 'meta' => ['upsert' => 'test']]]);
        $this->processor->process($this->context);

        self::assertFalse($this->context->has(SetOperationFlags::UPDATE_FLAG));
        self::assertFalse($this->context->has(SetOperationFlags::UPSERT_FLAG));
        self::assertFalse($this->context->has(SetOperationFlags::VALIDATE_FLAG));
        self::assertEquals(
            [
                Error::createValidationError(
                    Constraint::VALUE,
                    'This value should be a boolean or an array of strings.'
                )->setSource(ErrorSource::createByPointer('/meta/upsert'))
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWhenRequestDataContainsInvalidValidateFlagInMeta(): void
    {
        $this->context->setRequestData(['data' => ['type' => 'test', 'meta' => ['validate' => 'test']]]);
        $this->processor->process($this->context);

        self::assertFalse($this->context->has(SetOperationFlags::UPDATE_FLAG));
        self::assertFalse($this->context->has(SetOperationFlags::UPSERT_FLAG));
        self::assertFalse($this->context->has(SetOperationFlags::VALIDATE_FLAG));
        self::assertEquals(
            [
                Error::createValidationError(
                    Constraint::VALUE,
                    'This value should be a boolean.'
                )->setSource(ErrorSource::createByPointer('/meta/validate'))
            ],
            $this->context->getErrors()
        );
    }
}
