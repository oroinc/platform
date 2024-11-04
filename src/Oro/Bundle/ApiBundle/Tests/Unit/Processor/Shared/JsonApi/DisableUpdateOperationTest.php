<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared\JsonApi;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Shared\JsonApi\DisableUpdateOperation;
use Oro\Bundle\ApiBundle\Processor\Shared\JsonApi\SetOperationFlags;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;

class DisableUpdateOperationTest extends FormProcessorTestCase
{
    private DisableUpdateOperation $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->processor = new DisableUpdateOperation();
    }

    public function testProcessWhenUpdateOperationWasNotRequested(): void
    {
        $this->processor->process($this->context);
        self::assertFalse($this->context->hasErrors());
    }

    /**
     * @dataProvider updateFlagDataProvider
     */
    public function testProcessWhenUpdateOperationRequestedForPrimaryEntity(bool $updateFlag): void
    {
        $this->context->set(SetOperationFlags::UPDATE_FLAG, $updateFlag);
        $this->context->setMasterRequest(true);
        $this->processor->process($this->context);
        self::assertEquals(
            [
                Error::createValidationError(Constraint::VALUE, 'The option is not supported.')
                    ->setSource(ErrorSource::createByPointer('/meta/update'))
            ],
            $this->context->getErrors()
        );
    }

    /**
     * @dataProvider updateFlagDataProvider
     */
    public function testProcessWhenUpdateOperationRequestedForIncludedEntity(bool $updateFlag): void
    {
        $this->context->set(SetOperationFlags::UPDATE_FLAG, $updateFlag);
        $this->context->setMasterRequest(false);
        $this->processor->process($this->context);
        self::assertFalse($this->context->hasErrors());
    }

    public static function updateFlagDataProvider(): array
    {
        return [
            [false],
            [true]
        ];
    }
}
