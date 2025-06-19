<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Options\Rest;

use Oro\Bundle\ApiBundle\Processor\Options\Rest\SetCorsAllowMethods;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Options\OptionsProcessorTestCase;

class SetCorsAllowMethodsTest extends OptionsProcessorTestCase
{
    private SetCorsAllowMethods $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->processor = new SetCorsAllowMethods();
    }

    public function testAllowMethodsAreAlreadySet(): void
    {
        $this->context->getRequestHeaders()->set('Access-Control-Request-Method', 'POST');
        $this->context->getResponseHeaders()->set('Allow', 'GET, POST, PATCH');
        $this->context->getResponseHeaders()->set('Access-Control-Allow-Methods', 'OPTIONS, POST');
        $this->processor->process($this->context);

        self::assertSame(
            'OPTIONS, POST',
            $this->context->getResponseHeaders()->get('Access-Control-Allow-Methods')
        );
        self::assertTrue($this->context->getResponseHeaders()->has('Allow'));
    }

    public function testAllowMethodsWhenRequestedMethodIsAllowed(): void
    {
        $this->context->getRequestHeaders()->set('Access-Control-Request-Method', 'POST');
        $this->context->getResponseHeaders()->set('Allow', 'GET, POST, PATCH');
        $this->processor->process($this->context);

        self::assertSame(
            'GET, POST, PATCH',
            $this->context->getResponseHeaders()->get('Access-Control-Allow-Methods')
        );
        self::assertFalse($this->context->getResponseHeaders()->has('Allow'));
    }

    public function testAllowMethodsWhenRequestedMethodIsNotAllowed(): void
    {
        $this->context->getRequestHeaders()->set('Access-Control-Request-Method', 'DELETE');
        $this->context->getResponseHeaders()->set('Allow', 'GET, POST, PATCH');
        $this->processor->process($this->context);

        self::assertSame(
            'GET, POST, PATCH',
            $this->context->getResponseHeaders()->get('Access-Control-Allow-Methods')
        );
        self::assertFalse($this->context->getResponseHeaders()->has('Allow'));
    }

    public function testAllowMethodsWhenAllowHeaderIsNotSet(): void
    {
        $this->context->getRequestHeaders()->set('Access-Control-Request-Method', 'DELETE');
        $this->processor->process($this->context);

        self::assertFalse($this->context->getResponseHeaders()->has('Access-Control-Allow-Methods'));
        self::assertFalse($this->context->getResponseHeaders()->has('Allow'));
    }
}
