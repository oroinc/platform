<?php

namespace Oro\Bundle\AttachmentBundle\Api\Processor;

use Gaufrette\Exception\FileNotFound;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Psr\Log\LoggerInterface;

/**
 * Computes a value of "content" field for File entity.
 */
class ComputeFileContent implements ProcessorInterface
{
    private const CONTENT_FIELD_NAME = 'content';

    private FileManager $fileManager;
    private LoggerInterface $logger;

    public function __construct(FileManager $fileManager, LoggerInterface $logger)
    {
        $this->fileManager = $fileManager;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getData();

        if (!$context->isFieldRequested(self::CONTENT_FIELD_NAME, $data) || $this->isExternalFile($context)) {
            return;
        }

        $fileNameFieldName = $context->getResultFieldName('filename');

        if (!$fileNameFieldName || empty($data[$fileNameFieldName])) {
            return;
        }

        $content = $this->getFileContent($data[$fileNameFieldName]);
        if (null !== $content) {
            $data[self::CONTENT_FIELD_NAME] = $content;
            $context->setData($data);
        }
    }

    private function getFileContent(string $fileName): ?string
    {
        $content = null;
        try {
            $content = $this->fileManager->getContent($fileName);
        } catch (FileNotFound $e) {
            $this->logger->error(
                sprintf('The content for "%s" file cannot be loaded.', $fileName),
                ['exception' => $e]
            );
        }
        if (null !== $content) {
            $content = base64_encode($content);
        }

        return $content;
    }

    private function isExternalFile(ContextInterface $context): bool
    {
        $externalUrlFieldName = $context->getResultFieldName('externalUrl');
        $data = $context->getData();

        return $externalUrlFieldName && !empty($data[$externalUrlFieldName]);
    }
}
