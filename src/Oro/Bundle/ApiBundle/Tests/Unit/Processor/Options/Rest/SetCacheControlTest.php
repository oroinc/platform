<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Options\Rest;

use Oro\Bundle\ApiBundle\Processor\Options\Rest\SetCacheControl;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Options\OptionsProcessorTestCase;

class SetCacheControlTest extends OptionsProcessorTestCase
{
    public function testPreflightCacheIsEnabled()
    {
        $processor = new SetCacheControl(123);
        $processor->process($this->context);

        self::assertEquals(
            'max-age=123, public',
            $this->context->getResponseHeaders()->get('Cache-Control')
        );
        self::assertEquals('Origin', $this->context->getResponseHeaders()->get('Vary'));
    }

    public function testPreflightCacheIsDisabled()
    {
        $processor = new SetCacheControl(0);
        $processor->process($this->context);

        self::assertFalse($this->context->getResponseHeaders()->has('Cache-Control'));
        self::assertFalse($this->context->getResponseHeaders()->has('Vary'));
    }

    public function testPreflightCacheIsEnabledAndCacheControlHeaderIsAlreadySet()
    {
        $processor = new SetCacheControl(123);
        $this->context->getResponseHeaders()->set('Cache-Control', 'no-cache');
        $processor->process($this->context);

        self::assertEquals(
            'no-cache',
            $this->context->getResponseHeaders()->get('Cache-Control')
        );
        self::assertEquals('Origin', $this->context->getResponseHeaders()->get('Vary'));
    }
}
