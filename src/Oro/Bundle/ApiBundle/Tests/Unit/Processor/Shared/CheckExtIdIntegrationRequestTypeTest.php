<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\Shared\CheckExtIdIntegrationRequestType;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;

class CheckExtIdIntegrationRequestTypeTest extends GetListProcessorTestCase
{
    private CheckExtIdIntegrationRequestType $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->processor = new CheckExtIdIntegrationRequestType();
    }

    public function testProcessWhenIntegrationTypeRequestHeaderExistsWithExtIdValue(): void
    {
        $this->context->getRequestHeaders()->set('X-Integration-Type', 'ext_id');
        $this->processor->process($this->context);

        self::assertTrue($this->context->getRequestType()->contains('ext_id'));
    }

    public function testProcessWhenIntegrationTypeRequestHeaderExistsButRequestTypeAlreadyContainsExtIdAspect(): void
    {
        $this->context->getRequestType()->add('ext_id');
        $this->context->getRequestHeaders()->set('X-Integration-Type', 'ext_id');

        $this->processor->process($this->context);

        self::assertTrue($this->context->getRequestType()->contains('ext_id'));
    }

    public function testProcessWhenIntegrationTypeRequestHeaderExistsWithNotExtIdValue(): void
    {
        $this->context->getRequestHeaders()->set('X-Integration-Type', 'other');
        $this->processor->process($this->context);

        self::assertFalse($this->context->getRequestType()->contains('ext_id'));
    }

    public function testProcessWhenIntegrationTypeRequestHeaderNotExist(): void
    {
        $this->processor->process($this->context);

        self::assertFalse($this->context->getRequestType()->contains('ext_id'));
    }

    public function testProcessWhenIntegrationTypeRequestHeaderExistsWithEmptyValue(): void
    {
        $this->context->getRequestHeaders()->set('X-Integration-Type', '');
        $this->processor->process($this->context);

        self::assertFalse($this->context->getRequestType()->contains('ext_id'));
    }

    public function testProcessWhenIntegrationTypeRequestHeaderExistsWithNullValue(): void
    {
        $this->context->getRequestHeaders()->set('X-Integration-Type', null);
        $this->processor->process($this->context);

        self::assertFalse($this->context->getRequestType()->contains('ext_id'));
    }
}
