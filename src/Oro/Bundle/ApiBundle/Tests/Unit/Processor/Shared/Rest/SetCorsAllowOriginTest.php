<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared\Rest;

use Oro\Bundle\ApiBundle\Processor\Shared\Rest\SetCorsAllowOrigin;
use Oro\Bundle\ApiBundle\Request\Rest\CorsSettings;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;

class SetCorsAllowOriginTest extends GetListProcessorTestCase
{
    /**
     * @param string[] $allowedOrigins
     *
     * @return CorsSettings
     */
    private function getCorsSettings(array $allowedOrigins): CorsSettings
    {
        return new CorsSettings(
            0,
            $allowedOrigins,
            false,
            [],
            []
        );
    }

    public function testAllowOriginIsAlreadySet()
    {
        $processor = new SetCorsAllowOrigin($this->getCorsSettings(['https://foo.com', 'https://bar.com']));

        $this->context->getResponseHeaders()->set('Access-Control-Allow-Origin', '*');
        $this->context->getRequestHeaders()->set('Origin', 'https://baz.com');
        $this->context->setCorsRequest(true);
        $processor->process($this->context);

        self::assertEquals('*', $this->context->getResponseHeaders()->get('Access-Control-Allow-Origin'));
    }

    public function testNoOriginRequestHeader()
    {
        $processor = new SetCorsAllowOrigin($this->getCorsSettings([]));

        $this->context->getRequestHeaders()->set('Origin', 'https://foo.com');
        $this->context->setCorsRequest(true);
        $processor->process($this->context);

        self::assertFalse($this->context->getResponseHeaders()->has('Access-Control-Allow-Origin'));
    }

    public function testAllowOriginWhenOriginIsMatched()
    {
        $processor = new SetCorsAllowOrigin($this->getCorsSettings(['https://foo.com', 'https://bar.com']));

        $this->context->getRequestHeaders()->set('Origin', 'https://bar.com');
        $this->context->setCorsRequest(true);
        $processor->process($this->context);

        self::assertEquals(
            'https://bar.com',
            $this->context->getResponseHeaders()->get('Access-Control-Allow-Origin')
        );
    }

    public function testAllowOriginWhenOriginIsNotMatched()
    {
        $processor = new SetCorsAllowOrigin($this->getCorsSettings(['https://foo.com', 'https://bar.com']));

        $this->context->getRequestHeaders()->set('Origin', 'https://baz.com');
        $this->context->setCorsRequest(true);
        $processor->process($this->context);

        self::assertFalse($this->context->getResponseHeaders()->has('Access-Control-Allow-Origin'));
    }

    public function testAllowOriginWhenAllOriginsAreAllowed()
    {
        $processor = new SetCorsAllowOrigin($this->getCorsSettings(['https://foo.com', '*']));

        $this->context->getRequestHeaders()->set('Origin', 'https://bar.com');
        $this->context->setCorsRequest(true);
        $processor->process($this->context);

        self::assertEquals(
            'https://bar.com',
            $this->context->getResponseHeaders()->get('Access-Control-Allow-Origin')
        );
    }
}
