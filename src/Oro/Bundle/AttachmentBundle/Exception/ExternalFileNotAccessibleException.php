<?php

namespace Oro\Bundle\AttachmentBundle\Exception;

use Psr\Http\Message\ResponseInterface;

/**
 * Thrown when {@see ExternalFile} URL is not accessible.
 */
class ExternalFileNotAccessibleException extends \RuntimeException
{
    private string $url;

    private string $reason;

    private ?ResponseInterface $response;

    public function __construct(
        string $url,
        string $reason = '',
        ?\Throwable $previous = null,
        ?ResponseInterface $response = null,
        int $code = 0
    ) {
        parent::__construct(
            sprintf('Failed to fetch the external file metadata from URL "%s". Reason: "%s"', $url, $reason),
            $code,
            $previous
        );

        $this->url = $url;
        $this->reason = $reason;
        $this->response = $response;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }
}
