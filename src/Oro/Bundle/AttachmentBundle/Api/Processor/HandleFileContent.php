<?php

namespace Oro\Bundle\AttachmentBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Form\FormUtil;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Handles submitted content of File entity.
 */
class HandleFileContent implements ProcessorInterface
{
    private const CONTENT_FIELD_NAME = 'content';
    private const ORIGINAL_FILE_NAME_FIELD_NAME = 'originalFilename';

    private FileManager $fileManager;

    public function __construct(FileManager $fileManager)
    {
        $this->fileManager = $fileManager;
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        $data = $context->getData();
        if (!$data || !\array_key_exists(self::CONTENT_FIELD_NAME, $data)) {
            return;
        }

        if (!\array_key_exists(self::ORIGINAL_FILE_NAME_FIELD_NAME, $data)) {
            FormUtil::addFormError(
                $context->getForm(),
                sprintf(
                    'The "%s" field should be specified together with "%s" field.',
                    self::CONTENT_FIELD_NAME,
                    self::ORIGINAL_FILE_NAME_FIELD_NAME
                )
            );
            $data[self::CONTENT_FIELD_NAME] = null;
            $context->setData($data);

            return;
        }

        $content = $data[self::CONTENT_FIELD_NAME];
        if (null === $content) {
            return;
        }

        $decodedContent = base64_decode($content, true);
        if (false === $decodedContent) {
            FormUtil::addFormError($context->getForm(), 'Cannot decode content encoded with MIME base64.');
            $data[self::CONTENT_FIELD_NAME] = null;
        } else {
            $data[self::CONTENT_FIELD_NAME] = $this->fileManager->writeToTemporaryFile(
                $decodedContent,
                $data[self::ORIGINAL_FILE_NAME_FIELD_NAME]
            );
        }

        $context->setData($data);
    }
}
