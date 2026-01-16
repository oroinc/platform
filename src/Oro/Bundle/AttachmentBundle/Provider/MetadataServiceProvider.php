<?php

declare(strict_types=1);

namespace Oro\Bundle\AttachmentBundle\Provider;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Provides integration with external Metadata Service API for image metadata operations.
 */
class MetadataServiceProvider
{
    private const int HEALTH_CHECK_TIMEOUT = 5;
    private const int COPY_METADATA_TIMEOUT = 5;

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private ?string $serviceUrl,
        private ?string $apiKey,
    ) {
    }

    /**
     * Checks if Metadata Service is healthy and accessible.
     */
    public function isServiceHealthy(): bool
    {
        try {
            $response = $this->httpClient->request(
                'GET',
                sprintf('%s/health', $this->serviceUrl),
                [
                    'headers' => ['X-API-Key' => $this->apiKey],
                    'timeout' => self::HEALTH_CHECK_TIMEOUT
                ]
            );

            return $response->getStatusCode() === 200;
        } catch (TransportExceptionInterface $e) {
            $this->logger->warning(
                'Metadata Service health check failed.',
                [
                    'service_url' => $this->serviceUrl,
                    'exception' => $e->getMessage(),
                ]
            );

            return false;
        }
    }

    /**
     * Copies metadata from source image to target image using the Metadata Service API.
     */
    public function copyMetadata(string $sourceContent, string $targetContent): ?string
    {
        try {
            // Build binary payload: [4 bytes source length (big-endian)][source bytes][target bytes]
            $payload = pack('N', strlen($sourceContent)) . $sourceContent . $targetContent;

            $response = $this->httpClient->request(
                'POST',
                sprintf('%s/copy', $this->serviceUrl),
                [
                    'headers' => [
                        'X-API-Key' => $this->apiKey,
                        'Content-Type' => 'application/octet-stream'
                    ],
                    'body' => $payload,
                    'timeout' => self::COPY_METADATA_TIMEOUT
                ]
            );

            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200) {
                $this->logger->warning(
                    'Metadata Service returned non-success status code.',
                    [
                        'service_url' => $this->serviceUrl,
                        'status_code' => $statusCode,
                    ]
                );

                return null;
            }

            return $response->getContent();
        } catch (HttpExceptionInterface | TransportExceptionInterface $e) {
            $this->logger->warning(
                'Failed to copy metadata via Metadata Service.',
                [
                    'service_url' => $this->serviceUrl,
                    'exception' => $e->getMessage(),
                ]
            );

            return null;
        }
    }
}
