<?php

declare(strict_types=1);

namespace Oro\Bundle\ApiBundle\Processor\EnumOption;

use Oro\Bundle\ApiBundle\Form\FormUtil;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\TranslationBundle\Api\Model\TranslationCreate;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationRepository;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Entity\TranslationKey;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Oro\Bundle\TranslationBundle\Provider\LanguageProvider;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Checks translations submitted together with an enum option.
 * The usual checks for a separate translation request do not run for them. Before saving,
 * this class rejects a supplied key or domain, an unknown or repeated language, a translation
 * that already exists, and a missing or null translation value.
 */
class ValidateIncludedEnumOptionTranslations implements ProcessorInterface
{
    public function __construct(
        private readonly DoctrineHelper $doctrineHelper,
        private readonly LanguageProvider $languageProvider
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        $includedEntities = $context->getIncludedEntities();
        if (null === $includedEntities) {
            return;
        }

        /** @var EnumOption $option */
        $option = $context->getForm()->getData();
        $key = ExtendHelper::buildEnumOptionTranslationKey($option->getId());
        $availableLanguages = array_flip($this->languageProvider->getAvailableLanguageCodes());
        $seenLanguages = [];
        foreach ($includedEntities as $translation) {
            if (!$translation instanceof TranslationCreate) {
                continue;
            }
            if (TranslationKey::class !== $includedEntities->getClass($translation)) {
                continue;
            }

            $includedData = $includedEntities->getData($translation);
            $form = $includedData->getForm();
            if (null === $form || !$form->isSubmitted()) {
                continue;
            }

            $error = $this->getValidationError(
                $translation,
                $includedData->getRequestData() ?? [],
                $availableLanguages,
                $seenLanguages,
                $key
            );
            if (null !== $error) {
                FormUtil::addNamedFormError($form, Constraint::VALUE, $error[1], $error[0]);
            } else {
                $seenLanguages[$translation->getLanguageCode()] = true;
            }
        }
    }

    private function getValidationError(
        TranslationCreate $translation,
        array $requestData,
        array $availableLanguages,
        array $seenLanguages,
        string $key
    ): ?array {
        if (\array_key_exists('key', $requestData)) {
            return ['key', 'The translation key and domain are derived from the enum option.'];
        }
        if (\array_key_exists('domain', $requestData)) {
            return ['domain', 'The translation key and domain are derived from the enum option.'];
        }

        $languageCode = $translation->getLanguageCode();
        if (!isset($availableLanguages[$languageCode])) {
            return ['languageCode', 'The language is not installed.'];
        }
        if (isset($seenLanguages[$languageCode])) {
            return ['languageCode', 'A translation for this language is already included.'];
        }
        if ($this->hasTranslation($key, $languageCode)) {
            return ['languageCode', 'A translation for this language already exists. Use meta.update to update it.'];
        }
        if (null === $translation->getTranslatedValue()) {
            return ['translatedValue', 'The translated value is required.'];
        }

        return null;
    }

    private function hasTranslation(string $key, string $languageCode): bool
    {
        /** @var TranslationRepository $repository */
        $repository = $this->doctrineHelper->getEntityRepositoryForClass(Translation::class);

        return null !== $repository->findTranslation($key, $languageCode, TranslationManager::DEFAULT_DOMAIN);
    }
}
