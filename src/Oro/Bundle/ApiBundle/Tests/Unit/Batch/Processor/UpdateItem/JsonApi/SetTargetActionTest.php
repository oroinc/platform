<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Processor\UpdateItem\JsonApi;

use Oro\Bundle\ApiBundle\Batch\Processor\UpdateItem\JsonApi\SetTargetAction;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Tests\Unit\Batch\Processor\UpdateItem\BatchUpdateItemProcessorTestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class SetTargetActionTest extends BatchUpdateItemProcessorTestCase
{
    private SetTargetAction $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new SetTargetAction();
    }

    public function testProcessWithoutRequestData(): void
    {
        $this->processor->process($this->context);

        self::assertNull($this->context->getTargetAction());
    }

    public function testProcessWithEmptyRequestData(): void
    {
        $requestData = [];

        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        self::assertNull($this->context->getTargetAction());
    }

    public function testProcessWithNullData(): void
    {
        $requestData = [
            'data' => null
        ];

        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        self::assertEquals(ApiAction::CREATE, $this->context->getTargetAction());
    }

    public function testProcessWithoutMetaOptions(): void
    {
        $requestData = [
            'data' => []
        ];

        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        self::assertEquals(ApiAction::CREATE, $this->context->getTargetAction());
    }

    public function testProcessWithUpdateMetaOptionEqualsToTrue(): void
    {
        $requestData = [
            'data' => [
                'meta' => [
                    'update' => true
                ]
            ]
        ];

        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        self::assertEquals(ApiAction::UPDATE, $this->context->getTargetAction());
    }

    public function testProcessWithUpdateMetaOptionEqualsToFalse(): void
    {
        $requestData = [
            'data' => [
                'meta' => [
                    'update' => false
                ]
            ]
        ];

        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        self::assertEquals(ApiAction::CREATE, $this->context->getTargetAction());
    }

    /**
     * @dataProvider invalidUpdateOptionDataProvider
     */
    public function testProcessWithInvalidUpdateMetaOption(mixed $updateOptionValue): void
    {
        $requestData = [
            'data' => [
                'meta' => [
                    'update' => $updateOptionValue
                ]
            ]
        ];
        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        self::assertEquals(ApiAction::CREATE, $this->context->getTargetAction());
    }

    public function invalidUpdateOptionDataProvider(): array
    {
        return [
            [null],
            ['test']
        ];
    }

    public function testProcessWithUpsertMetaOptionEqualsToTrue(): void
    {
        $requestData = [
            'data' => [
                'meta' => [
                    'upsert' => true
                ]
            ]
        ];

        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        self::assertEquals(ApiAction::UPDATE, $this->context->getTargetAction());
    }

    public function testProcessWithUpsertMetaOptionEqualsToFalse(): void
    {
        $requestData = [
            'data' => [
                'meta' => [
                    'upsert' => false
                ]
            ]
        ];

        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        self::assertEquals(ApiAction::CREATE, $this->context->getTargetAction());
    }

    public function testProcessWithUpsertMetaOptionEqualsToIdArray(): void
    {
        $requestData = [
            'data' => [
                'meta' => [
                    'upsert' => ['id']
                ]
            ]
        ];

        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        self::assertEquals(ApiAction::UPDATE, $this->context->getTargetAction());
    }

    public function testProcessWithUpsertMetaOptionEqualsToArrayOfFields(): void
    {
        $requestData = [
            'data' => [
                'meta' => [
                    'upsert' => ['field1', 'field2']
                ]
            ]
        ];

        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        self::assertEquals(ApiAction::CREATE, $this->context->getTargetAction());
    }

    /**
     * @dataProvider invalidUpsertOptionDataProvider
     */
    public function testProcessWithInvalidUpsertMetaOption(mixed $upsertOptionValue): void
    {
        $requestData = [
            'data' => [
                'meta' => [
                    'upsert' => $upsertOptionValue
                ]
            ]
        ];

        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        self::assertEquals(ApiAction::CREATE, $this->context->getTargetAction());
    }

    public function invalidUpsertOptionDataProvider(): array
    {
        return [
            [null],
            ['test'],
            [[]],
            [['field1', '']],
            [['field1', ' ']],
            [['field1', 123]],
            [['key1' => 'val1', 'key2' => 'val2']]
        ];
    }

    public function testProcessWithBothUpdateAndUpsertMetaOptionsEqualsToTrue(): void
    {
        $requestData = [
            'data' => [
                'meta' => [
                    'update' => true,
                    'upsert' => true
                ]
            ]
        ];

        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        self::assertEquals(ApiAction::CREATE, $this->context->getTargetAction());
    }

    public function testProcessWithBothUpdateAndUpsertMetaOptionsEqualsToFalse(): void
    {
        $requestData = [
            'data' => [
                'meta' => [
                    'update' => false,
                    'upsert' => false
                ]
            ]
        ];

        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        self::assertEquals(ApiAction::CREATE, $this->context->getTargetAction());
    }
}
