<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Form\DataMapper;

use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate as EmailTemplateEntity;
use Oro\Bundle\EmailBundle\Provider\EmailTemplateTranslationResolver;
use Symfony\Component\Form\DataMapperInterface;

/**
 * Creates a form data mapper for {@see EmailTemplateEntity}.
 */
class EmailTemplateDataMapperFactory
{
    public function __construct(
        private readonly EmailTemplateTranslationResolver $emailTemplateTranslationResolver,
        private readonly FileManager $fileManager,
    ) {
    }

    public function createDataMapper(?DataMapperInterface $innerDataMapper = null): DataMapperInterface
    {
        return new EmailTemplateDataMapper(
            $this->emailTemplateTranslationResolver,
            $this->fileManager,
            $innerDataMapper
        );
    }
}
