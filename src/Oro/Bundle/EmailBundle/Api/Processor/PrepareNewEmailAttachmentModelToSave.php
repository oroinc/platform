<?php

namespace Oro\Bundle\EmailBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\EmailBundle\Api\Model\EmailAttachment as EmailAttachmentModel;
use Oro\Bundle\EmailBundle\Builder\EmailEntityBuilder;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Prepares the Email model created by "create" action to save into the database.
 */
class PrepareNewEmailAttachmentModelToSave implements ProcessorInterface
{
    private EmailEntityBuilder $emailEntityBuilder;

    public function __construct(EmailEntityBuilder $emailEntityBuilder)
    {
        $this->emailEntityBuilder = $emailEntityBuilder;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        /** @var EmailAttachmentModel $attachmentModel */
        $attachmentModel = $context->getData();

        $attachment = $this->emailEntityBuilder->attachment(
            $attachmentModel->getFileName(),
            $attachmentModel->getContentType()
        );
        $attachment->setContent($this->emailEntityBuilder->attachmentContent(
            $attachmentModel->getContent(),
            $attachmentModel->getContentEncoding()
        ));
        $attachment->setEmbeddedContentId($attachmentModel->getEmbeddedContentId());
        $attachmentModel->setEntity($attachment);

        $context->setData($attachment);
    }
}
