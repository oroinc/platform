<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Update\JsonApi;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Shared\JsonApi\SetOperationFlags;
use Oro\Bundle\ApiBundle\Processor\Update\JsonApi\DisableUpsertOperation;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Update\UpdateProcessorTestCase;

class DisableUpsertOperationTest extends UpdateProcessorTestCase
{
    private DisableUpsertOperation $processor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->processor = new DisableUpsertOperation();
    }

    public function testProcessWhenUpsertOperationWasNotRequested(): void
    {
        $this->context->setMetadata(new EntityMetadata('Test\Entity'));
        $this->processor->process($this->context);
        self::assertFalse($this->context->hasErrors());
    }

    public function testProcessWhenValueOfUpsertOptionIsFalse(): void
    {
        $this->context->set(SetOperationFlags::UPSERT_FLAG, false);
        $this->context->setMetadata(new EntityMetadata('Test\Entity'));
        $this->processor->process($this->context);
        self::assertFalse($this->context->hasErrors());
    }

    /**
     * @dataProvider upsertFlagDataProvider
     */
    public function testProcessWhenUpsertOperationRequestedForEntityWithoutApiMetadata(bool|array $upsertFlag): void
    {
        $this->context->set(SetOperationFlags::UPSERT_FLAG, $upsertFlag);
        $this->context->setMetadata(null);
        $this->processor->process($this->context);
        self::assertEquals(
            [
                Error::createValidationError(Constraint::VALUE, 'The upsert operation is not supported.')
                    ->setSource(ErrorSource::createByPointer('/meta/upsert'))
            ],
            $this->context->getErrors()
        );
    }

    /**
     * @dataProvider upsertFlagDataProvider
     */
    public function testProcessWhenUpsertOperationRequestedForEntityWithoutIdGenerator(bool|array $upsertFlag): void
    {
        $this->context->set(SetOperationFlags::UPSERT_FLAG, $upsertFlag);
        $this->context->setMetadata(new EntityMetadata('Test\Entity'));
        $this->processor->process($this->context);
        self::assertFalse($this->context->hasErrors());
    }

    /**
     * @dataProvider upsertFlagDataProvider
     */
    public function testProcessWhenUpsertOperationRequestedForEntityWithIdGenerator(bool|array $upsertFlag): void
    {
        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setHasIdentifierGenerator(true);
        $this->context->set(SetOperationFlags::UPSERT_FLAG, $upsertFlag);
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);
        self::assertEquals(
            [
                Error::createValidationError(
                    Constraint::VALUE,
                    'The upsert operation is not supported for resources with auto-generated identifier value.'
                )->setSource(ErrorSource::createByPointer('/meta/upsert'))
            ],
            $this->context->getErrors()
        );
    }

    public static function upsertFlagDataProvider(): array
    {
        return [
            [true],
            [['field1']]
        ];
    }
}
