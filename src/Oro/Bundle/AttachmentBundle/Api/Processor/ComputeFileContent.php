<?php

namespace Oro\Bundle\AttachmentBundle\Api\Processor;

use Gaufrette\Exception\FileNotFound;

use Psr\Log\LoggerInterface;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedDataContext;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;

/**
 * Computes a value of "content" field for File entity.
 */
class ComputeFileContent implements ProcessorInterface
{
    /** @var FileManager */
    protected $fileManager;

    /** @var LoggerInterface */
    protected $logger;

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

        $config = $context->getConfig();
        $contentField = $config->getField('content');
        if (!$contentField || $contentField->isExcluded()) {
            return;
        }

        if (empty($data['filename'])) {
            return;
        }

        $content = $this->getFileContent($data['filename']);
        if (null !== $content) {
            $data[$contentField->getPropertyPath()] = $content;
            $context->setResult($data);
        }
    }

    /**
     * @param string $fileName
     *
     * @return string|null
     */
    protected function getFileContent($fileName)
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
