<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Options\Rest;

use Oro\Bundle\ApiBundle\Processor\Options\Rest\SetCorsMaxAge;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Options\OptionsProcessorTestCase;

class SetCorsMaxAgeTest extends OptionsProcessorTestCase
{
    public function testMaxAgeIsAlreadySet()
    {
        $processor = new SetCorsMaxAge(123);
        $this->context->getResponseHeaders()->set('Access-Control-Max-Age', 234);
        $this->context->getRequestHeaders()->set('Access-Control-Request-Method', 'POST');
        $processor->process($this->context);

        self::assertSame(234, $this->context->getResponseHeaders()->get('Access-Control-Max-Age'));
    }

    public function testPreflightCacheIsDisabledForPreflightRequest()
    {
        $processor = new SetCorsMaxAge(0);
        $this->context->getRequestHeaders()->set('Access-Control-Request-Method', 'POST');
        $processor->process($this->context);

        self::assertFalse($this->context->getResponseHeaders()->has('Access-Control-Max-Age'));
    }

    public function testPreflightCacheIsEnabledForPreflightRequest()
    {
        $processor = new SetCorsMaxAge(123);
        $this->context->getRequestHeaders()->set('Access-Control-Request-Method', 'POST');
        $processor->process($this->context);

        self::assertSame(123, $this->context->getResponseHeaders()->get('Access-Control-Max-Age'));
    }

    public function testPreflightCacheIsEnabledForNotPreflightRequest()
    {
        $processor = new SetCorsMaxAge(123);
        $processor->process($this->context);

        self::assertFalse($this->context->getResponseHeaders()->has('Access-Control-Max-Age'));
    }
}
