<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Options\Rest;

use Oro\Bundle\ApiBundle\Processor\Options\Rest\SetCacheControl;
use Oro\Bundle\ApiBundle\Request\Rest\CorsSettings;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Options\OptionsProcessorTestCase;

class SetCacheControlTest extends OptionsProcessorTestCase
{
    private function getCorsSettings(int $preflightMaxAge): CorsSettings
    {
        return new CorsSettings(
            $preflightMaxAge,
            [],
            false,
            [],
            []
        );
    }

    public function testPreflightCacheIsEnabled()
    {
        $processor = new SetCacheControl($this->getCorsSettings(123));
        $processor->process($this->context);

        self::assertEquals(
            'max-age=123, public',
            $this->context->getResponseHeaders()->get('Cache-Control')
        );
        self::assertEquals('Origin', $this->context->getResponseHeaders()->get('Vary'));
    }

    public function testPreflightCacheIsDisabled()
    {
        $processor = new SetCacheControl($this->getCorsSettings(0));
        $processor->process($this->context);

        self::assertFalse($this->context->getResponseHeaders()->has('Cache-Control'));
        self::assertFalse($this->context->getResponseHeaders()->has('Vary'));
    }

    public function testPreflightCacheIsEnabledAndCacheControlHeaderIsAlreadySet()
    {
        $processor = new SetCacheControl($this->getCorsSettings(123));
        $this->context->getResponseHeaders()->set('Cache-Control', 'no-cache');
        $processor->process($this->context);

        self::assertEquals(
            'no-cache',
            $this->context->getResponseHeaders()->get('Cache-Control')
        );
        self::assertEquals('Origin', $this->context->getResponseHeaders()->get('Vary'));
    }
}
