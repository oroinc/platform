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

    /** @var FileManager */
    private $fileManager;

    /** @var LoggerInterface */
    private $logger;

    /**
     * @param FileManager     $fileManager
     * @param LoggerInterface $logger
     */
    public function __construct(FileManager $fileManager, LoggerInterface $logger)
    {
        $this->fileManager = $fileManager;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getResult();
        if (!is_array($data)) {
            return;
        }

        if (!$context->isFieldRequested(self::CONTENT_FIELD_NAME, $data)) {
            return;
        }

        $fileNameFieldName = $context->getResultFieldName('filename');
        if (!$fileNameFieldName || empty($data[$fileNameFieldName])) {
            return;
        }

        $content = $this->getFileContent($data[$fileNameFieldName]);
        if (null !== $content) {
            $data[self::CONTENT_FIELD_NAME] = $content;
            $context->setResult($data);
        }
    }

    /**
     * @param string $fileName
     *
     * @return string|null
     */
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
}
