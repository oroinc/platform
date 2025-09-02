<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Provider;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate as EmailTemplateEntity;
use Oro\Bundle\EmailBundle\Entity\EmailTemplateAttachment;
use Oro\Bundle\EmailBundle\Entity\EmailTemplateTranslation;
use Oro\Bundle\EmailBundle\Model\EmailTemplateAttachmentModel;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Resolves the translation for a field in {@see EmailTemplateEntity} according to fallback and localization tree.
 */
class EmailTemplateTranslationResolver
{
    public function __construct(
        private readonly PropertyAccessorInterface $propertyAccessor
    ) {
    }

    /**
     * @param EmailTemplateEntity $emailTemplateEntity
     * @param string $fieldName Field name to get translated value for, e.g. 'subject' or 'content'.
     * @param Localization|null $localization Localization to get translated value for.
     *  Use null to get the default value.
     *
     * @return mixed Translated value of the field.
     */
    public function resolveTranslation(
        EmailTemplateEntity $emailTemplateEntity,
        string $fieldName,
        ?Localization $localization
    ): mixed {
        $translatedValue = $this->resolveTranslationValue($emailTemplateEntity, $fieldName, $localization);

        if ($translatedValue instanceof Collection) {
            $translatedValue = clone $translatedValue;
        }

        if ($fieldName === 'attachments') {
            /**
             * @var string $key
             * @var EmailTemplateAttachment $emailTemplateAttachment
             */
            foreach ($translatedValue as $key => $emailTemplateAttachment) {
                $translatedValue[$key] = new EmailTemplateAttachmentModel();
                $translatedValue[$key]->setId($emailTemplateAttachment->getId());
                $translatedValue[$key]->setFile($emailTemplateAttachment->getFile());
                $translatedValue[$key]->setFilePlaceholder($emailTemplateAttachment->getFilePlaceholder());
            }
        }

        return $translatedValue;
    }

    /**
     * @param EmailTemplateEntity $emailTemplateEntity
     * @param string $fieldName Field name to get translated value for, e.g. 'subject' or 'content'.
     * @param Localization|null $localization Localization to get translated value for.
     *  Use null to get the default value.
     *
     * @return mixed Translated value of the field.
     */
    private function resolveTranslationValue(
        EmailTemplateEntity $emailTemplateEntity,
        string $fieldName,
        ?Localization $localization
    ): mixed {
        if ($localization === null) {
            return $this->propertyAccessor->getValue($emailTemplateEntity, $fieldName);
        }

        $templateTranslationsByLocalization = [];
        foreach ($emailTemplateEntity->getTranslations() as $translation) {
            $templateTranslationsByLocalization[$translation->getLocalization()->getId()] = $translation;
        }

        $attributeFallback = $fieldName . 'Fallback';

        while ($templateTranslation = $this->findTranslation($templateTranslationsByLocalization, $localization)) {
            if (!$this->propertyAccessor->getValue($templateTranslation, $attributeFallback)) {
                return $this->propertyAccessor->getValue($templateTranslation, $fieldName);
            }

            $localization = $templateTranslation->getLocalization()?->getParentLocalization();
        }

        return $this->propertyAccessor->getValue($emailTemplateEntity, $fieldName);
    }

    private function findTranslation(
        array &$templateTranslationsByLocalization,
        ?Localization $localization
    ): ?EmailTemplateTranslation {
        while ($localization && $templateTranslationsByLocalization) {
            if (isset($templateTranslationsByLocalization[$localization->getId()])) {
                $template = $templateTranslationsByLocalization[$localization->getId()];

                unset($templateTranslationsByLocalization[$localization->getId()]);

                return $template;
            }

            $localization = $localization->getParentLocalization();
        }

        return null;
    }
}
