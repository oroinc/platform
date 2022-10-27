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
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Creates {@see ExternalFile} from the specified URL.
 * Creates {@see ExternalFile} from the {@see File} entity.
 */
class ExternalFileFactory implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private ClientInterface $httpClient;

    private array $httpOptions;

    public function __construct(ClientInterface $httpClient, array $httpOptions = [])
    {
        $this->httpClient = $httpClient;
        $this->httpOptions = $httpOptions;
        $this->logger = new NullLogger();
    }

    /**
     * Creates {@see ExternalFile} from the {@see File} entity.
     *
     * @param File $file
     *
     * @return ExternalFile|null
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
     * @param string $url
     *
     * @return ExternalFile
     *
     * @throws ExternalFileNotAccessibleException
     */
    public function createFromUrl(string $url): ExternalFile
    {
        try {
            $response = $this->httpClient->request('HEAD', $url, $this->getHttpOptions());
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

        if ($response->getStatusCode() !== 200) {
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

    private function getHttpOptions(): array
    {
        return $this->httpOptions + [
                RequestOptions::HTTP_ERRORS => false,
                RequestOptions::ALLOW_REDIRECTS => true,
                RequestOptions::CONNECT_TIMEOUT => 30,
                RequestOptions::TIMEOUT => 30,
            ];
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
