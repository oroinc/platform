<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Model\WebhookRequestProcessor;

use Oro\Bundle\IntegrationBundle\Model\WebhookRequestProcessor\WebhookRequestContext;
use PHPUnit\Framework\TestCase;

class WebhookRequestContextTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $payload = ['topic' => 'order.created', 'eventData' => ['id' => 1]];
        $httpMethod = 'POST';
        $headers = ['Content-Type' => 'application/json'];
        $requestOptions = ['verify_peer' => true, 'max_redirects' => 0];
        $metadata = ['entity_class' => 'Order', 'entity_id' => 42];

        $context = new WebhookRequestContext($payload, $httpMethod, $headers, $requestOptions, $metadata);

        self::assertSame($payload, $context->getPayload());
        self::assertSame($httpMethod, $context->getHttpMethod());
        self::assertSame($headers, $context->getHeaders());
        self::assertSame($requestOptions, $context->getRequestOptions());
        self::assertSame($metadata, $context->getMetadata());
    }

    public function testSetPayload(): void
    {
        $context = $this->createContext();
        $newPayload = ['topic' => 'product.deleted'];

        $context->setPayload($newPayload);

        self::assertSame($newPayload, $context->getPayload());
    }

    public function testSetHttpMethod(): void
    {
        $context = $this->createContext();

        $context->setHttpMethod('PUT');

        self::assertSame('PUT', $context->getHttpMethod());
    }

    public function testSetHeaders(): void
    {
        $context = $this->createContext();
        $newHeaders = ['Authorization' => 'Bearer token'];

        $context->setHeaders($newHeaders);

        self::assertSame($newHeaders, $context->getHeaders());
    }

    public function testSetRequestOptions(): void
    {
        $context = $this->createContext();
        $newOptions = ['verify_peer' => false, 'timeout' => 30];

        $context->setRequestOptions($newOptions);

        self::assertSame($newOptions, $context->getRequestOptions());
    }

    public function testSetMetadata(): void
    {
        $context = $this->createContext();
        $newMetadata = ['foo' => 'bar'];

        $context->setMetadata($newMetadata);

        self::assertSame($newMetadata, $context->getMetadata());
    }

    public function testEmptyArraysAllowed(): void
    {
        $context = new WebhookRequestContext([], 'GET', [], [], []);

        self::assertSame([], $context->getPayload());
        self::assertSame('GET', $context->getHttpMethod());
        self::assertSame([], $context->getHeaders());
        self::assertSame([], $context->getRequestOptions());
        self::assertSame([], $context->getMetadata());
    }

    private function createContext(): WebhookRequestContext
    {
        return new WebhookRequestContext(
            ['topic' => 'order.created'],
            'POST',
            ['Content-Type' => 'application/json'],
            ['verify_peer' => true],
            []
        );
    }
}
