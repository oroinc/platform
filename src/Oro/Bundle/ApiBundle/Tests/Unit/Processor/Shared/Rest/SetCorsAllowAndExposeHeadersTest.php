<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared\Rest;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Processor\Shared\Rest\SetCorsAllowAndExposeHeaders;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;

class SetCorsAllowAndExposeHeadersTest extends \PHPUnit\Framework\TestCase
{
    /** @var Context */
    private $context;

    protected function setUp()
    {
        $this->context = new Context(
            $this->createMock(ConfigProvider::class),
            $this->createMock(MetadataProvider::class)
        );
    }

    public function testAllowHeadersForNotPreflightRequest()
    {
        $processor = new SetCorsAllowAndExposeHeaders([], [], false);
        $processor->process($this->context);

        self::assertFalse($this->context->getResponseHeaders()->has('Access-Control-Allow-Headers'));
    }

    public function testDefaultAllowHeadersForPreflightRequest()
    {
        $processor = new SetCorsAllowAndExposeHeaders([], [], false);
        $this->context->getRequestHeaders()->set('Access-Control-Request-Method', 'POST');
        $processor->process($this->context);

        self::assertEquals(
            'Content-Type,X-Include',
            $this->context->getResponseHeaders()->get('Access-Control-Allow-Headers')
        );
    }

    public function testAllowHeadersForPreflightRequestWithAdditionalHeaders()
    {
        $processor = new SetCorsAllowAndExposeHeaders(['AllowHeader1'], [], false);
        $this->context->getRequestHeaders()->set('Access-Control-Request-Method', 'POST');
        $processor->process($this->context);

        self::assertEquals(
            'Content-Type,X-Include,AllowHeader1',
            $this->context->getResponseHeaders()->get('Access-Control-Allow-Headers')
        );
    }

    public function testAllowHeadersWhenTheyAreAlreadySet()
    {
        $processor = new SetCorsAllowAndExposeHeaders([], [], false);
        $this->context->getResponseHeaders()->set('Access-Control-Allow-Headers', 'header1,header2');
        $this->context->getRequestHeaders()->set('Access-Control-Request-Method', 'POST');
        $processor->process($this->context);

        self::assertEquals(
            'header1,header2',
            $this->context->getResponseHeaders()->get('Access-Control-Allow-Headers')
        );
    }

    public function testDefaultExposeHeaders()
    {
        $processor = new SetCorsAllowAndExposeHeaders([], [], false);
        $processor->process($this->context);

        self::assertEquals(
            'Location,X-Include-Total-Count,X-Include-Deleted-Count',
            $this->context->getResponseHeaders()->get('Access-Control-Expose-Headers')
        );
    }

    public function testExposeHeadersWithAdditionalHeaders()
    {
        $processor = new SetCorsAllowAndExposeHeaders([], ['ExposeHeader1'], false);
        $processor->process($this->context);

        self::assertEquals(
            'Location,X-Include-Total-Count,X-Include-Deleted-Count,ExposeHeader1',
            $this->context->getResponseHeaders()->get('Access-Control-Expose-Headers')
        );
    }

    public function testExposeHeadersWhenTheyAreAlreadySet()
    {
        $processor = new SetCorsAllowAndExposeHeaders([], [], false);
        $this->context->getResponseHeaders()->set('Access-Control-Expose-Headers', 'header1,header2,header2');
        $processor->process($this->context);

        self::assertEquals(
            'header1,header2,header2',
            $this->context->getResponseHeaders()->get('Access-Control-Expose-Headers')
        );
    }

    public function testCredentialsAllowed()
    {
        $processor = new SetCorsAllowAndExposeHeaders([], [], true);
        $processor->process($this->context);

        self::assertEquals(
            'true',
            $this->context->getResponseHeaders()->get('Access-Control-Allow-Credentials')
        );
    }

    public function testCredentialsNotAllowed()
    {
        $processor = new SetCorsAllowAndExposeHeaders([], ['ExposeHeader1'], false);
        $processor->process($this->context);

        self::assertFalse(
            $this->context->getResponseHeaders()->has('Access-Control-Allow-Credentials')
        );
    }

    public function testAllowCredentialHeaderIsAlreadySet()
    {
        $processor = new SetCorsAllowAndExposeHeaders([], [], false);
        $this->context->getResponseHeaders()->set('Access-Control-Allow-Credentials', 'true');
        $processor->process($this->context);

        self::assertEquals(
            'true',
            $this->context->getResponseHeaders()->get('Access-Control-Allow-Credentials')
        );
    }
}
