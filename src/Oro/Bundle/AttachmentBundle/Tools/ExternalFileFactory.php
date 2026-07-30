<?php

namespace Oro\Bundle\AttachmentBundle\Tools;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Exception\ExternalFileNotAccessibleException;
use Oro\Bundle\AttachmentBundle\Model\ExternalFile;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;

/**
 * Creates {@see ExternalFile} from a specified URL or {@see File} entity.
 */
class ExternalFileFactory
{
    public function __construct(
        private ClientInterface $httpClient,
        private array $httpOptions,
        private LoggerInterface $logger,
        private ConfigManager $configManager
    ) {
    }

    /**
     * Creates {@see ExternalFile} from the {@see File} entity.
     */
    public function createFromFile(File $file): ?ExternalFile
    {
        if (!$file->getExternalUrl()) {
            return null;
        }

        return new ExternalFile(
            $file->getExternalUrl(),
            (string)($file->getOriginalFilename() ?: $file->getFilename()),
            (int)$file->getFileSize(),
            (string)$file->getMimeType()
        );
    }

    /**
     * Creates {@see ExternalFile} from the specified URL.
     * Makes a HEAD request to fetch file size, MIME type, file name.
     * Returns ExternalFile with $error property set if an error occurs.
     *
     * @throws ExternalFileNotAccessibleException when the given URL is not accessible by some reasons
     */
    public function createFromUrl(string $url): ExternalFile
    {
        $isReceivedInfo = false;
        $methods = $this->getUrlMethods($url);

        foreach ($methods as $method) {
            try {
                $response = $this->httpClient->request($method, $url, $this->getHttpOptions());
            } catch (RequestException $exception) {
                $reason = (string)$exception->getResponse()?->getReasonPhrase();
                if (!$reason) {
                    $reason = $this->getHandlerContextError($exception->getHandlerContext());
                }

                throw new ExternalFileNotAccessibleException(
                    $url,
                    $reason,
                    $exception,
                    $exception->getResponse()
                );
            } catch (ConnectException $exception) {
                throw new ExternalFileNotAccessibleException(
                    $url,
                    $this->getHandlerContextError($exception->getHandlerContext()),
                    $exception
                );
            } catch (GuzzleException $exception) {
                $this->logger->error(
                    'Failed to make a HEAD request when creating an external file for {url}: {error}',
                    [
                        'exception' => $exception,
                        'url' => $url,
                        'error' => $exception->getMessage(),
                    ]
                );

                throw new ExternalFileNotAccessibleException($url, $exception->getMessage(), $exception);
            }

            if ($response->getStatusCode() === 200) {
                $isReceivedInfo = true;
                break;
            }
        }

        if (!$isReceivedInfo) {
            throw new ExternalFileNotAccessibleException(
                $url,
                $response->getReasonPhrase(),
                null,
                $response
            );
        }

        return new ExternalFile(
            $url,
            $this->getOriginalFilename($response),
            $this->getFileSize($response),
            $this->getMimeType($response)
        );
    }

    private function getUrlMethods(string $url): array
    {
        $methods = ['HEAD'];
        $urlOptions = $this->configManager->get('oro_attachment.external_file_details_http_methods') ?? [];
        foreach ($urlOptions as $urlOption) {
            if (preg_match($urlOption['regex'], $url) && !empty($urlOption['methods'])) {
                $methods = $urlOption['methods'];
            }
        }

        return $methods;
    }

    private function getHttpOptions(): array
    {
        return $this->httpOptions + [
                RequestOptions::HTTP_ERRORS => false,
                RequestOptions::ALLOW_REDIRECTS => [
                    'max' => 5,
                    'strict' => false,
                    'referer' => false,
                    'protocols' => ['http', 'https'],
                    'on_redirect' => function (
                        RequestInterface $request,
                        ResponseInterface $response,
                        UriInterface $uri
                    ): void {
                        $this->assertRedirectTargetAllowed((string)$uri);
                    },
                ],
                RequestOptions::CONNECT_TIMEOUT => 30,
                RequestOptions::TIMEOUT => 30,
            ];
    }

    /**
     * Ensures that a redirect target is still permitted: it must match the configured allowed URLs
     * regular expression. This prevents following a redirect from an allowed host to a URL that would
     * not be accepted if it had been submitted directly (SSRF).
     *
     * @throws ExternalFileNotAccessibleException when the redirect target is not allowed
     */
    private function assertRedirectTargetAllowed(string $url): void
    {
        $regExp = $this->getExternalFileAllowedUrlsRegExp();
        if (!$regExp || !preg_match($regExp, $url)) {
            $this->logger->error(
                'Redirect to a URL that is not allowed by the external file URL configuration {url}',
                [
                    'url' => $url
                ]
            );

            throw new ExternalFileNotAccessibleException(
                $url,
                'Redirect to a URL that is not allowed by the external file URL configuration.'
            );
        }
    }

    private function getExternalFileAllowedUrlsRegExp(): string
    {
        $regExp = (string)$this->configManager->get('oro_attachment.external_file_allowed_urls_regexp');

        return $regExp ? '~' . $regExp . '~i' : '';
    }

    private function getOriginalFilename(ResponseInterface $response): string
    {
        $contentDispositionParts = $response->getHeader('Content-Disposition');
        if ($contentDispositionParts) {
            foreach ($contentDispositionParts as $part) {
                if (preg_match('/filename=["\']?(?P<name>.+?)["\']?$/u', $part, $matches)) {
                    $fileName = $matches['name'];
                    break;
                }
            }
        }

        return $fileName ?? '';
    }

    private function getMimeType(ResponseInterface $response): string
    {
        $contentTypeParts = $response->getHeader('Content-Type');
        if ($contentTypeParts) {
            foreach ($contentTypeParts as $part) {
                if (preg_match('/(?P<type>\w+\/[-+.\w]+)/', $part, $matches)) {
                    $mimeType = $matches['type'];
                    break;
                }
            }
        }

        return $mimeType ?? '';
    }

    private function getFileSize(ResponseInterface $response): int
    {
        $contentLength = $response->getHeaderLine('Content-Length');
        if (!is_numeric($contentLength) || $contentLength < 0) {
            $contentLength = 0;
        }

        return $contentLength;
    }

    private function getHandlerContextError(array $handlerContext): string
    {
        return $handlerContext['error'] ?? '';
    }
}
