<?php

namespace Oro\Bundle\TranslationBundle\Api\Processor;

use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Filter\FilterNamesRegistry;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Entity\TranslationKey;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Builds ORM QueryBuilder object that will be used to get a list of translations.
 */
class BuildTranslationQuery implements ProcessorInterface
{
    private const HAS_TRANSLATION_FIELD_NAME = 'hasTranslation';
    private const TRANSLATED_VALUE_FIELD_NAME = 'translatedValue';

    private DoctrineHelper $doctrineHelper;
    private FilterNamesRegistry $filterNamesRegistry;

    public function __construct(DoctrineHelper $doctrineHelper, FilterNamesRegistry $filterNamesRegistry)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->filterNamesRegistry = $filterNamesRegistry;
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        if ($context->hasQuery()) {
            return;
        }

        $query = $this->doctrineHelper->createQueryBuilder(TranslationKey::class, 'e')
            ->innerJoin(Language::class, 'language', Join::WITH, '1 = 1');
        if ($this->isTranslationJoinRequired($context)) {
            $query->leftJoin(
                Translation::class,
                'translation',
                Join::WITH,
                'translation.translationKey = e AND translation.language = language'
            );
        }
        $context->setQuery($query);
    }

    private function isTranslationJoinRequired(Context $context): bool
    {
        $config = $context->getConfig();
        if ($this->isFieldRequested($config, self::HAS_TRANSLATION_FIELD_NAME)
            || $this->isFieldRequested($config, self::TRANSLATED_VALUE_FIELD_NAME)
        ) {
            return true;
        }

        $filterValueAccessor = $context->getFilterValues();
        if ($filterValueAccessor->has(self::HAS_TRANSLATION_FIELD_NAME)
            || $filterValueAccessor->has(self::TRANSLATED_VALUE_FIELD_NAME)
        ) {
            return true;
        }

        $sortFilterValue = $filterValueAccessor->getOne(
            $this->filterNamesRegistry->getFilterNames($context->getRequestType())->getSortFilterName()
        );

        return
            null !== $sortFilterValue
            && \array_key_exists(self::HAS_TRANSLATION_FIELD_NAME, $sortFilterValue->getValue());
    }

    private function isFieldRequested(?EntityDefinitionConfig $config, string $fieldName): bool
    {
        return null !== $config && !$config->getField($fieldName)->isExcluded();
    }
}
