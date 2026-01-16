<?php

declare(strict_types=1);

namespace Oro\Bundle\AttachmentBundle\Imagine\PostProcessor;

use Liip\ImagineBundle\Binary\BinaryInterface;
use Liip\ImagineBundle\Imagine\Filter\PostProcessor\PostProcessorInterface;
use Liip\ImagineBundle\Model\Binary;
use Oro\Bundle\AttachmentBundle\Provider\MetadataServiceProvider;
use Psr\Log\LoggerInterface;

/**
 * Post-processor that uses Metadata Service API to preserve original image metadata.
 */
class MetadataServicePostProcessor implements PostProcessorInterface
{
    public function __construct(
        private MetadataServiceProvider $metadataServiceProvider,
        private LoggerInterface $logger,
    ) {
    }

    #[\Override]
    public function process(BinaryInterface $binary, array $options = []): BinaryInterface
    {
        if (!isset($options['original_content'])) {
            return $binary;
        }

        if (!$this->metadataServiceProvider->isServiceHealthy()) {
            $this->logger->warning(
                'Metadata Service is not healthy. Skipping metadata preservation.',
                ['file_name' => $options['file_name'] ?? null]
            );

            return $binary;
        }

        $fileName = $options['file_name'] ?? null;
        $originalContent = $options['original_content'];
        $processedContent = $binary->getContent();

        $resultContent = $this->metadataServiceProvider->copyMetadata(
            $originalContent,
            $processedContent
        );

        if ($resultContent === null) {
            $this->logger->warning(
                'Failed to copy metadata from Metadata Service.',
                [
                    'file_name' => $fileName,
                    'source_size' => strlen($originalContent),
                    'target_size' => strlen($processedContent)
                ]
            );

            return $binary;
        }

        return new Binary($resultContent, $binary->getMimeType(), $binary->getFormat());
    }
}
