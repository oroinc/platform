<?php

namespace Oro\Bundle\EmailBundle\EmbeddedImages;

use Oro\Bundle\EmailBundle\Builder\EmailEntityBuilder;
use Oro\Bundle\EmailBundle\Form\Model\Email as EmailModel;
use Oro\Bundle\EmailBundle\Form\Model\Factory;

/**
 * Extracts embedded images from {@see \Oro\Bundle\EmailBundle\Form\Model\Email} model body and
 * adds corresponding attachments.
 */
class EmbeddedImagesInEmailModelHandler
{
    private EmbeddedImagesExtractor $embeddedImagesExtractor;

    private EmailEntityBuilder $emailEntityBuilder;

    private Factory $emailModelsFactory;

    public function __construct(
        EmbeddedImagesExtractor $embeddedImagesExtractor,
        EmailEntityBuilder $emailEntityBuilder,
        Factory $emailModelsFactory
    ) {
        $this->emailEntityBuilder = $emailEntityBuilder;
        $this->embeddedImagesExtractor = $embeddedImagesExtractor;
        $this->emailModelsFactory = $emailModelsFactory;
    }

    public function handleEmbeddedImages(EmailModel $emailModel): void
    {
        $emailBody = $emailModel->getBody();
        if (!$emailBody) {
            return;
        }

        $embeddedImages = $this->embeddedImagesExtractor->extractEmbeddedImages($emailBody);
        if (!$embeddedImages) {
            return;
        }

        foreach ($embeddedImages as $embeddedImage) {
            $emailAttachmentContentEntity = $this->emailEntityBuilder->attachmentContent(
                $embeddedImage->getEncodedContent(),
                $embeddedImage->getEncoding()
            );

            $emailAttachmentEntity = $this->emailEntityBuilder
                ->attachment($embeddedImage->getFilename(), $embeddedImage->getContentType())
                ->setEmbeddedContentId($embeddedImage->getFilename())
                ->setContent($emailAttachmentContentEntity);

            $emailAttachmentModel = $this->emailModelsFactory->getEmailAttachment()
                ->setEmailAttachment($emailAttachmentEntity);

            $emailModel->addAttachment($emailAttachmentModel);
        }

        $emailModel->setBody($emailBody);
    }
}
