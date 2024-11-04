<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared\Rest;

use Oro\Bundle\ApiBundle\Processor\Shared\Rest\ValidateRequestType;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\GetProcessorTestCase;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;

class ValidateRequestTypeTest extends GetProcessorTestCase
{
    private ValidateRequestType $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new ValidateRequestType();
    }

    public function testNoAcceptAndContentTypeHeaders(): void
    {
        $this->processor->process($this->context);
    }

    /**
     * @dataProvider supportedAcceptHeaderDataProvider
     */
    public function testSupportedAcceptHeader(array|string $acceptHeaderValue): void
    {
        $this->context->getRequestHeaders()->set('Accept', $acceptHeaderValue);
        $this->processor->process($this->context);
    }

    public static function supportedAcceptHeaderDataProvider(): array
    {
        return [
            ['application/json'],
            ['application/*'],
            ['*/*'],
            [['application/xml', 'application/json;q=0.5']]
        ];
    }

    public function testUnsupportedAcceptHeader(): void
    {
        $this->expectException(NotAcceptableHttpException::class);
        $this->expectExceptionMessage('Only JSON representation of the requested resource is supported.');

        $this->context->getRequestHeaders()->set('Accept', 'application/xml');
        $this->processor->process($this->context);
    }

    /**
     * @dataProvider supportedContentTypeHeaderDataProvider
     */
    public function testSupportedContentTypeHeader(string $contentTypeHeaderValue): void
    {
        $this->context->getRequestHeaders()->set('Content-Type', $contentTypeHeaderValue);
        $this->processor->process($this->context);
    }

    public static function supportedContentTypeHeaderDataProvider(): array
    {
        return [
            ['application/json'],
            ['application/json;q=0.5']
        ];
    }

    public function testUnsupportedContentTypeHeader(): void
    {
        $this->expectException(NotAcceptableHttpException::class);
        $this->expectExceptionMessage('The "Content-Type" request header must be "application/json" if specified.');

        $this->context->getRequestHeaders()->set('Content-Type', 'application/xml');
        $this->processor->process($this->context);
    }
}
