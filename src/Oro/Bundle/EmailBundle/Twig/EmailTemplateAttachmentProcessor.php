<?php

namespace Oro\Bundle\EmailBundle\Twig;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Entity\FileItem;
use Oro\Bundle\EmailBundle\Model\EmailTemplateAttachmentModel;
use Oro\Bundle\EntityBundle\Twig\Sandbox\TemplateDataFactory;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Processes email template attachments: creates an email template attachment model with the content
 * either from the existing file or the one resolved from the file placeholder.
 */
class EmailTemplateAttachmentProcessor implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly TemplateDataFactory $templateDataFactory
    ) {
        $this->logger = new NullLogger();
    }

    public function processAttachment(
        EmailTemplateAttachmentModel $emailTemplateAttachment,
        array $templateParams = []
    ): ?EmailTemplateAttachmentModel {
        $processedEmailTemplateAttachment = new EmailTemplateAttachmentModel();
        $processedEmailTemplateAttachment->setId($emailTemplateAttachment->getId());
        $processedEmailTemplateAttachment->setFile($emailTemplateAttachment->getFile());
        $processedEmailTemplateAttachment->setFilePlaceholder($emailTemplateAttachment->getFilePlaceholder());

        if (!$processedEmailTemplateAttachment->getFile()) {
            $resolvedValue = $this->resolveFilePlaceholder($emailTemplateAttachment, $templateParams);
            if ($resolvedValue instanceof File) {
                $processedEmailTemplateAttachment->setFile($resolvedValue);
            } elseif ($resolvedValue instanceof Collection) {
                foreach ($resolvedValue as $index => $fileItem) {
                    if (!$fileItem instanceof FileItem) {
                        $this->logger->error(
                            'The file placeholder "{file_placeholder}" is expected to be computed into '
                            . 'a {file} entity or collection of {file_item} entities, '
                            . 'but got "{file_type}" at {index}.',
                            [
                                'file_placeholder' => $emailTemplateAttachment->getFilePlaceholder(),
                                'file' => File::class,
                                'file_item' => FileItem::class,
                                'file_type' => get_debug_type($fileItem),
                                'index' => $index,
                            ]
                        );

                        continue;
                    }

                    $processedEmailTemplateAttachment->addFileItem($fileItem);
                }
            } elseif ($resolvedValue !== null) {
                $this->logger->error(
                    'The file placeholder "{file_placeholder}" is expected to be computed into '
                    . 'a {file} entity or collection of {file_item} entities, but got "{file_type}".',
                    [
                        'file_placeholder' => $emailTemplateAttachment->getFilePlaceholder(),
                        'file' => File::class,
                        'file_item' => FileItem::class,
                        'file_type' => get_debug_type($resolvedValue),
                    ]
                );
            }
        }

        return $processedEmailTemplateAttachment;
    }

    private function resolveFilePlaceholder(
        EmailTemplateAttachmentModel $emailTemplateAttachment,
        array $templateParams
    ): mixed {
        $filePlaceholder = $emailTemplateAttachment->getFilePlaceholder();
        if ($filePlaceholder) {
            return $this->templateDataFactory
                ->createTemplateData($templateParams)
                ->getEntityVariable($filePlaceholder);
        }

        return null;
    }
}
