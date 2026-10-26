<?php

declare(strict_types=1);

namespace Oro\Bundle\ApiBundle\Processor\EnumOption;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumOptionRepository;
use Oro\Bundle\TranslationBundle\Api\Model\TranslationCreate;
use Oro\Bundle\TranslationBundle\Api\Model\TranslationUpdate;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Updates the stored name and locale and clears other defaults when this option is set as default.
 */
class SynchronizeEnumOptionNameAndDefaults implements ProcessorInterface
{
    public function __construct(
        private readonly DoctrineHelper $doctrineHelper
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        if (!$context->getForm()->isValid()) {
            return;
        }

        $enumOption = $context->getData();
        if (!$enumOption instanceof EnumOption) {
            return;
        }

        if (ApiAction::CREATE === $context->getParentAction()) {
            $this->setCreatedOptionName($context, $enumOption);
        } else {
            $this->setUpdatedOptionName($context, $enumOption);
        }

        $this->clearOtherDefaultOptions($context, $enumOption);
    }

    private function setCreatedOptionName(CustomizeFormDataContext $context, EnumOption $enumOption): void
    {
        $translation = null;
        $includedEntities = $context->getIncludedEntities();
        if (null !== $includedEntities) {
            foreach ($includedEntities as $included) {
                if (!$included instanceof TranslationCreate) {
                    continue;
                }

                if (null === $translation) {
                    $translation = $included;
                }
                if (Translator::DEFAULT_LOCALE === $included->getLanguageCode()) {
                    $translation = $included;
                    break;
                }
            }
        }

        $enumOption->setName($translation?->getTranslatedValue() ?? $enumOption->getInternalId());
        $enumOption->setLocale($translation?->getLanguageCode() ?? Translator::DEFAULT_LOCALE);
    }

    private function setUpdatedOptionName(CustomizeFormDataContext $context, EnumOption $enumOption): void
    {
        $includedEntities = $context->getIncludedEntities();
        if (null === $includedEntities) {
            return;
        }

        foreach ($includedEntities as $included) {
            if (
                $included instanceof TranslationUpdate
                && Translator::DEFAULT_LOCALE === $included->getLanguageCode()
                && null !== $included->getTranslatedValue()
            ) {
                $enumOption->setName($included->getTranslatedValue());
                $enumOption->setLocale(Translator::DEFAULT_LOCALE);

                return;
            }
        }
    }

    private function clearOtherDefaultOptions(CustomizeFormDataContext $context, EnumOption $enumOption): void
    {
        if (!$enumOption->isDefault() || !$context->findFormField('default')?->isSubmitted()) {
            return;
        }

        /** @var EnumOptionRepository $repository */
        $repository = $this->doctrineHelper->getEntityRepositoryForClass(EnumOption::class);
        $defaultOptions = $repository->getDefaultValues($enumOption->getEnumCode());
        foreach ($defaultOptions as $defaultOption) {
            if ($defaultOption->getId() !== $enumOption->getId()) {
                $defaultOption->setDefault(false);
            }
        }
    }
}
