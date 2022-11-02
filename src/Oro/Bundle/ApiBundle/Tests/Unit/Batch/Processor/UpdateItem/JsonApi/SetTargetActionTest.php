<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Processor\UpdateItem\JsonApi;

use Oro\Bundle\ApiBundle\Batch\Processor\UpdateItem\JsonApi\SetTargetAction;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Tests\Unit\Batch\Processor\UpdateItem\BatchUpdateItemProcessorTestCase;

class SetTargetActionTest extends BatchUpdateItemProcessorTestCase
{
    /** @var SetTargetAction */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new SetTargetAction();
    }

    public function testProcessWithoutRequestData()
    {
        $this->processor->process($this->context);

        self::assertNull($this->context->getTargetAction());
    }

    public function testProcessWithEmptyRequestData()
    {
        $data = [];

        $this->context->setRequestData($data);
        $this->processor->process($this->context);

        self::assertNull($this->context->getTargetAction());
    }

    public function testProcessWithNullData()
    {
        $data = [
            'data' => null
        ];

        $this->context->setRequestData($data);
        $this->processor->process($this->context);

        self::assertEquals(ApiAction::CREATE, $this->context->getTargetAction());
    }

    public function testProcessWithoutMetaOptions()
    {
        $data = [
            'data' => []
        ];

        $this->context->setRequestData($data);
        $this->processor->process($this->context);

        self::assertEquals(ApiAction::CREATE, $this->context->getTargetAction());
    }

    public function testProcessWithUpdateMetaOption()
    {
        $data = [
            'data' => [
                'meta' => [
                    'update' => true
                ]
            ]
        ];

        $this->context->setRequestData($data);
        $this->processor->process($this->context);

        self::assertEquals(ApiAction::UPDATE, $this->context->getTargetAction());
    }
}
