<?php

namespace Oro\Bundle\AttachmentBundle\Api\Processor;

use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;

/**
 * Adds event listener for File entity form to process a file content.
 */
class AddFileContentFormListener implements ProcessorInterface
{
    const CONTENT_FIELD_NAME            = 'content';
    const ORIGINAL_FILE_NAME_FIELD_NAME = 'originalFilename';
    const MIME_TYPE_FIELD_NAME          = 'mimeType';

    /** @var FileManager */
    protected $fileManager;

    /**
     * @param FileManager $fileManager
     */
    public function __construct(FileManager $fileManager)
    {
        $this->fileManager = $fileManager;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var FormContext $context */

        $formBuilder = $context->getFormBuilder();
        if (!$formBuilder) {
            // the form builder does not exist
            return;
        }

        $formBuilder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'onPreSubmit']);
    }

    /**
     * @param FormEvent $event
     */
    public function onPreSubmit(FormEvent $event)
    {
        $data = $event->getData();
        if (!$data || !array_key_exists(self::CONTENT_FIELD_NAME, $data)) {
            return;
        }

        if (!array_key_exists(self::ORIGINAL_FILE_NAME_FIELD_NAME, $data)) {
            $event->getForm()->addError(
                new FormError(
                    sprintf(
                        'The "%s" field should be specified together with "%s" field.',
                        self::CONTENT_FIELD_NAME,
                        self::ORIGINAL_FILE_NAME_FIELD_NAME
                    )
                )
            );
            $data[self::CONTENT_FIELD_NAME] = null;
            $event->setData($data);

            return;
        }

        $content = $data[self::CONTENT_FIELD_NAME];
        if (null === $content) {
            return;
        }

        $decodedContent = base64_decode($content, true);
        if (false === $decodedContent) {
            $event->getForm()->addError(new FormError('Cannot decode content encoded with MIME base64.'));
            $data[self::CONTENT_FIELD_NAME] = null;
        } else {
            $file = $this->fileManager->writeToTemporaryFile(
                $decodedContent,
                $data[self::ORIGINAL_FILE_NAME_FIELD_NAME]
            );
            if (!empty($data[self::MIME_TYPE_FIELD_NAME])) {
                $file = new UploadedFile(
                    $file->getPathname(),
                    $data[self::ORIGINAL_FILE_NAME_FIELD_NAME],
                    $data[self::MIME_TYPE_FIELD_NAME]
                );
            }
            $data[self::CONTENT_FIELD_NAME] = $file;
        }

        $event->setData($data);
    }
}
