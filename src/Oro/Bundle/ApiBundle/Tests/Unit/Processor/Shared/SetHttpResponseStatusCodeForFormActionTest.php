<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Shared\SetHttpResponseStatusCodeForFormAction;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Update\UpdateProcessorTestCase;
use Symfony\Component\HttpFoundation\Response;

class SetHttpResponseStatusCodeForFormActionTest extends UpdateProcessorTestCase
{
    private SetHttpResponseStatusCodeForFormAction $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new SetHttpResponseStatusCodeForFormAction();
    }

    public function testProcessWhenResponseStatusCodeAlreadySet(): void
    {
        $this->context->setResponseStatusCode(Response::HTTP_ACCEPTED);
        $this->context->setResult([]);
        $this->processor->process($this->context);

        self::assertEquals(Response::HTTP_ACCEPTED, $this->context->getResponseStatusCode());
    }

    public function testProcessWhenHasErrors(): void
    {
        $this->context->addError(new Error());
        $this->context->setResult([]);
        $this->processor->process($this->context);

        self::assertNull($this->context->getResponseStatusCode());
    }

    public function testProcessWhenNoResult(): void
    {
        $this->processor->process($this->context);

        self::assertNull($this->context->getResponseStatusCode());
    }

    public function testProcessForCreatedEntity(): void
    {
        $this->context->setExisting(false);
        $this->context->setResult([]);
        $this->processor->process($this->context);

        self::assertEquals(Response::HTTP_CREATED, $this->context->getResponseStatusCode());
    }

    public function testProcessForUpdatedEntity(): void
    {
        $this->context->setExisting(true);
        $this->context->setResult([]);
        $this->processor->process($this->context);

        self::assertEquals(Response::HTTP_OK, $this->context->getResponseStatusCode());
    }
}
