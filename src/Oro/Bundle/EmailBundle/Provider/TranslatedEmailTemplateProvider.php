<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Provider;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate as EmailTemplateEntity;
use Oro\Bundle\EmailBundle\Model\EmailTemplate as EmailTemplateModel;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Provides an {@see EmailTemplateModel} with the subject and content translated according to the given localization.
 */
class TranslatedEmailTemplateProvider
{
    private array $translatableFields = ['subject', 'content', 'attachments'];

    public function __construct(
        private readonly EmailTemplateTranslationResolver $emailTemplateTranslationResolver,
        private readonly PropertyAccessorInterface $propertyAccessor
    ) {
    }

    public function setTranslatableFields(array $translatableFields): void
    {
        $this->translatableFields = $translatableFields;
    }

    public function getTranslatedEmailTemplate(
        EmailTemplateEntity $emailTemplateEntity,
        ?Localization $localization = null
    ): EmailTemplateModel {
        $emailTemplateModel = (new EmailTemplateModel())
            ->setName($emailTemplateEntity->getName())
            ->setEntityName($emailTemplateEntity->getEntityName())
            ->setType($emailTemplateEntity->getType());

        foreach ($this->translatableFields as $fieldName) {
            $translatedValue = $this->emailTemplateTranslationResolver
                ->resolveTranslation($emailTemplateEntity, $fieldName, $localization);

            $this->propertyAccessor->setValue($emailTemplateModel, $fieldName, $translatedValue);
        }

        return $emailTemplateModel;
    }
}
