<?php

namespace Oro\Bundle\AttachmentBundle\Api\Processor;

use Gaufrette\Exception\FileNotFound;

use Psr\Log\LoggerInterface;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedDataContext;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;

/**
 * Computes a value of "content" field for File entity.
 */
class ComputeFileContent implements ProcessorInterface
{
    /** @var AttachmentManager */
    protected $attachmentManager;

    /** @var LoggerInterface */
    protected $logger;

    /**
     * @param AttachmentManager $attachmentManager
     * @param LoggerInterface   $logger
     */
    public function __construct(AttachmentManager $attachmentManager, LoggerInterface $logger)
    {
        $this->attachmentManager = $attachmentManager;
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
        $contextFieldName = $config->findFieldNameByPropertyPath('content');
        if (!$contextFieldName
            || $config->getField($contextFieldName)->isExcluded()
            || array_key_exists($contextFieldName, $data)
        ) {
            return;
        }

        $fileNameFieldName = $config->findFieldNameByPropertyPath('filename');
        $data[$contextFieldName] = $fileNameFieldName && !empty($data[$fileNameFieldName])
            ? $this->getFileContent($data[$fileNameFieldName])
            : null;
        $context->setResult($data);
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
            $content = $this->attachmentManager->getContent($fileName);
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
