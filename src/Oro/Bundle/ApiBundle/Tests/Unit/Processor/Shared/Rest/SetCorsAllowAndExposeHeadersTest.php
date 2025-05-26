<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared\Rest;

use Oro\Bundle\ApiBundle\Processor\Shared\Rest\SetCorsAllowAndExposeHeaders;
use Oro\Bundle\ApiBundle\Request\Rest\CorsSettings;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;

class SetCorsAllowAndExposeHeadersTest extends GetListProcessorTestCase
{
    /**
     * @param bool     $isCredentialsAllowed
     * @param string[] $allowedHeaders
     * @param string[] $exposableHeaders
     *
     * @return CorsSettings
     */
    private function getCorsSettings(
        bool $isCredentialsAllowed,
        array $allowedHeaders,
        array $exposableHeaders
    ): CorsSettings {
        return new CorsSettings(
            0,
            [],
            $isCredentialsAllowed,
            $allowedHeaders,
            $exposableHeaders
        );
    }

    public function testAllowHeadersForNotPreflightRequest(): void
    {
        $processor = new SetCorsAllowAndExposeHeaders($this->getCorsSettings(false, [], []));
        $processor->process($this->context);

        self::assertFalse($this->context->getResponseHeaders()->has('Access-Control-Allow-Headers'));
    }

    public function testDefaultAllowHeadersForPreflightRequest(): void
    {
        $processor = new SetCorsAllowAndExposeHeaders($this->getCorsSettings(false, [], []));
        $this->context->getRequestHeaders()->set('Access-Control-Request-Method', 'POST');
        $processor->process($this->context);

        self::assertEquals(
            'Authorization,Content-Type,X-Include',
            $this->context->getResponseHeaders()->get('Access-Control-Allow-Headers')
        );
    }

    public function testAllowHeadersForPreflightRequestWithAdditionalHeaders(): void
    {
        $processor = new SetCorsAllowAndExposeHeaders($this->getCorsSettings(false, ['AllowHeader1'], []));
        $this->context->getRequestHeaders()->set('Access-Control-Request-Method', 'POST');
        $processor->process($this->context);

        self::assertEquals(
            'Authorization,Content-Type,X-Include,AllowHeader1',
            $this->context->getResponseHeaders()->get('Access-Control-Allow-Headers')
        );
    }

    public function testAllowHeadersWhenTheyAreAlreadySet(): void
    {
        $processor = new SetCorsAllowAndExposeHeaders($this->getCorsSettings(false, [], []));
        $this->context->getResponseHeaders()->set('Access-Control-Allow-Headers', 'header1,header2');
        $this->context->getRequestHeaders()->set('Access-Control-Request-Method', 'POST');
        $processor->process($this->context);

        self::assertEquals(
            'header1,header2',
            $this->context->getResponseHeaders()->get('Access-Control-Allow-Headers')
        );
    }

    public function testDefaultExposeHeaders(): void
    {
        $processor = new SetCorsAllowAndExposeHeaders($this->getCorsSettings(false, [], []));
        $processor->process($this->context);

        self::assertEquals(
            'Location,X-Include-Total-Count,X-Include-Deleted-Count',
            $this->context->getResponseHeaders()->get('Access-Control-Expose-Headers')
        );
    }

    public function testExposeHeadersWithAdditionalHeaders(): void
    {
        $processor = new SetCorsAllowAndExposeHeaders($this->getCorsSettings(false, [], ['ExposeHeader1']));
        $processor->process($this->context);

        self::assertEquals(
            'Location,X-Include-Total-Count,X-Include-Deleted-Count,ExposeHeader1',
            $this->context->getResponseHeaders()->get('Access-Control-Expose-Headers')
        );
    }

    public function testExposeHeadersWhenTheyAreAlreadySet(): void
    {
        $processor = new SetCorsAllowAndExposeHeaders($this->getCorsSettings(false, [], []));
        $this->context->getResponseHeaders()->set('Access-Control-Expose-Headers', 'header1,header2,header2');
        $processor->process($this->context);

        self::assertEquals(
            'header1,header2,header2',
            $this->context->getResponseHeaders()->get('Access-Control-Expose-Headers')
        );
    }

    public function testCredentialsAllowed(): void
    {
        $processor = new SetCorsAllowAndExposeHeaders($this->getCorsSettings(true, [], []));
        $processor->process($this->context);

        self::assertEquals(
            'true',
            $this->context->getResponseHeaders()->get('Access-Control-Allow-Credentials')
        );
    }

    public function testCredentialsNotAllowed(): void
    {
        $processor = new SetCorsAllowAndExposeHeaders($this->getCorsSettings(false, [], ['ExposeHeader1']));
        $processor->process($this->context);

        self::assertFalse(
            $this->context->getResponseHeaders()->has('Access-Control-Allow-Credentials')
        );
    }

    public function testAllowCredentialHeaderIsAlreadySet(): void
    {
        $processor = new SetCorsAllowAndExposeHeaders($this->getCorsSettings(false, [], []));
        $this->context->getResponseHeaders()->set('Access-Control-Allow-Credentials', 'true');
        $processor->process($this->context);

        self::assertEquals(
            'true',
            $this->context->getResponseHeaders()->get('Access-Control-Allow-Credentials')
        );
    }
}
