<?php

declare(strict_types=1);

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Provider;

use Oro\Bundle\AttachmentBundle\Provider\MetadataServiceProvider;
use Oro\Bundle\AttachmentBundle\Tests\Unit\Fixtures\Exception\TransportException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class MetadataServiceProviderTest extends TestCase
{
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;
    private MetadataServiceProvider $provider;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->provider = new MetadataServiceProvider(
            $this->httpClient,
            $this->logger,
            'http://localhost:8080',
            'test-api-key'
        );
    }

    public function testIsServiceHealthyWhenServiceIsAvailable(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->expects(self::once())
            ->method('getStatusCode')
            ->willReturn(200);

        $this->httpClient->expects(self::once())
            ->method('request')
            ->with(
                'GET',
                'http://localhost:8080/health',
                [
                    'headers' => ['X-API-Key' => 'test-api-key'],
                    'timeout' => 5
                ]
            )
            ->willReturn($response);

        self::assertTrue($this->provider->isServiceHealthy());
    }

    public function testIsServiceHealthyWhenServiceReturnsNon200(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->expects(self::once())
            ->method('getStatusCode')
            ->willReturn(500);

        $this->httpClient->expects(self::once())
            ->method('request')
            ->with(
                'GET',
                'http://localhost:8080/health',
                [
                    'headers' => ['X-API-Key' => 'test-api-key'],
                    'timeout' => 5
                ]
            )
            ->willReturn($response);

        self::assertFalse($this->provider->isServiceHealthy());
    }

    public function testIsServiceHealthyWhenExceptionThrown(): void
    {
        $exception = new TransportException('Connection failed');

        $this->httpClient->expects(self::once())
            ->method('request')
            ->with(
                'GET',
                'http://localhost:8080/health',
                [
                    'headers' => ['X-API-Key' => 'test-api-key'],
                    'timeout' => 5
                ]
            )
            ->willThrowException($exception);

        $this->logger->expects(self::once())
            ->method('warning')
            ->with(
                'Metadata Service health check failed.',
                [
                    'service_url' => 'http://localhost:8080',
                    'exception' => 'Connection failed',
                ]
            );

        self::assertFalse($this->provider->isServiceHealthy());
    }

    public function testCopyMetadataSuccess(): void
    {
        $sourceContent = 'source-image-content';
        $targetContent = 'target-image-content';
        $expectedPayload = pack('N', strlen($sourceContent)) . $sourceContent . $targetContent;
        $expectedResult = 'result-image-content';

        $response = $this->createMock(ResponseInterface::class);
        $response->expects(self::once())
            ->method('getStatusCode')
            ->willReturn(200);
        $response->expects(self::once())
            ->method('getContent')
            ->willReturn($expectedResult);

        $this->httpClient->expects(self::once())
            ->method('request')
            ->with(
                'POST',
                'http://localhost:8080/copy',
                [
                    'headers' => [
                        'X-API-Key' => 'test-api-key',
                        'Content-Type' => 'application/octet-stream'
                    ],
                    'body' => $expectedPayload,
                    'timeout' => 5
                ]
            )
            ->willReturn($response);

        $result = $this->provider->copyMetadata($sourceContent, $targetContent);

        self::assertSame($expectedResult, $result);
    }

    public function testCopyMetadataReturnsNullWhenServiceReturnsNon200(): void
    {
        $sourceContent = 'source-image-content';
        $targetContent = 'target-image-content';

        $response = $this->createMock(ResponseInterface::class);
        $response->expects(self::once())
            ->method('getStatusCode')
            ->willReturn(500);
        $response->expects(self::never())
            ->method('getContent');

        $this->httpClient->expects(self::once())
            ->method('request')
            ->willReturn($response);

        $this->logger->expects(self::once())
            ->method('warning')
            ->with(
                'Metadata Service returned non-success status code.',
                [
                    'service_url' => 'http://localhost:8080',
                    'status_code' => 500,
                ]
            );

        $result = $this->provider->copyMetadata($sourceContent, $targetContent);

        self::assertNull($result);
    }

    public function testCopyMetadataReturnsNullWhenExceptionThrown(): void
    {
        $sourceContent = 'source-image-content';
        $targetContent = 'target-image-content';
        $exception = new TransportException('Request timeout');

        $this->httpClient->expects(self::once())
            ->method('request')
            ->willThrowException($exception);

        $this->logger->expects(self::once())
            ->method('warning')
            ->with(
                'Failed to copy metadata via Metadata Service.',
                [
                    'service_url' => 'http://localhost:8080',
                    'exception' => 'Request timeout',
                ]
            );

        $result = $this->provider->copyMetadata($sourceContent, $targetContent);

        self::assertNull($result);
    }
}
