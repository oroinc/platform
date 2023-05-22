<?php

namespace Oro\Bundle\TranslationBundle\Api\Processor;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\TranslationBundle\Api\TranslationIdUtil;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Adds a restriction by the primary entity identifier to the ORM QueryBuilder
 * that is used to load a translation.
 */
class AddTranslationIdToQuery implements ProcessorInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var SingleItemContext $context */

        $query = $context->getQuery();
        if (!$query instanceof QueryBuilder) {
            return;
        }

        $translationId = $context->getId();
        $query
            ->where('e.id = :id AND language.code = :langCode')
            ->setParameter('id', TranslationIdUtil::extractTranslationKeyId($translationId) ?? 0)
            ->setParameter('langCode', TranslationIdUtil::extractLanguageCode($translationId) ?? '');
    }
}
