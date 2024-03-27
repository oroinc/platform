<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Provider;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate as EmailTemplateEntity;
use Oro\Bundle\EmailBundle\Entity\EmailTemplateTranslation;
use Oro\Bundle\EmailBundle\Model\EmailTemplate as EmailTemplateModel;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Provides an {@see EmailTemplateModel} with the subject and content translated according to the given localization.
 */
class TranslatedEmailTemplateProvider
{
    private PropertyAccessorInterface $propertyAccessor;

    private array $translatableFields = ['subject', 'content'];

    public function __construct(PropertyAccessorInterface $propertyAccessor)
    {
        $this->propertyAccessor = $propertyAccessor;
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

        $templateTranslationsByLocalization = [];
        foreach ($emailTemplateEntity->getTranslations() as $translation) {
            $templateTranslationsByLocalization[$translation->getLocalization()->getId()] = $translation;
        }

        foreach ($this->translatableFields as $fieldName) {
            $translatedValue = $this->getTranslatedValue(
                $templateTranslationsByLocalization,
                $localization,
                $emailTemplateEntity,
                $fieldName
            );

            $this->propertyAccessor->setValue($emailTemplateModel, $fieldName, $translatedValue);
        }

        return $emailTemplateModel;
    }

    /**
     * Finds a translation for the localization tree based on the fallback attribute.
     * When a translation fallbacks to the localization without a parent takes the corresponding value from
     * EmailTemplateEntity.
     */
    private function getTranslatedValue(
        array $templateTranslationsByLocalization,
        ?Localization $localization,
        EmailTemplateEntity $emailTemplateEntity,
        string $fieldName
    ): string {
        $attributeFallback = $fieldName . 'Fallback';

        while ($templateTranslation = $this->findTranslation($templateTranslationsByLocalization, $localization)) {
            // For current attribute not enabled fallback to parent localizations
            if (!$this->propertyAccessor->getValue($templateTranslation, $attributeFallback)) {
                return $this->propertyAccessor->getValue($templateTranslation, $fieldName);
            }

            // Find next available localized template by localization tree
            $localization = $templateTranslation->getLocalization()?->getParentLocalization();
        }

        // Fallback to default when template for localization not found
        return $this->propertyAccessor->getValue($emailTemplateEntity, $fieldName);
    }

    private function findTranslation(
        array &$templateTranslationsByLocalization,
        ?Localization $localization
    ): ?EmailTemplateTranslation {
        while ($localization && $templateTranslationsByLocalization) {
            if (isset($templateTranslationsByLocalization[$localization->getId()])) {
                $template = $templateTranslationsByLocalization[$localization->getId()];

                // Prevents possible infinite loop on a looped localization tree.
                unset($templateTranslationsByLocalization[$localization->getId()]);

                return $template;
            }

            $localization = $localization->getParentLocalization();
        }

        return null;
    }
}
