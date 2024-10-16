<?php

namespace Oro\Bundle\TranslationBundle\Api\Filter;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\Expression;
use Oro\Bundle\ApiBundle\Exception\InvalidFilterValueException;
use Oro\Bundle\ApiBundle\Filter\AbstractCompositeIdentifierFilter;
use Oro\Bundle\TranslationBundle\Api\TranslationIdUtil;

/**
 * The filter that is used to filter translation entities by a value of composite identifier used in API.
 */
class TranslationIdFilter extends AbstractCompositeIdentifierFilter
{
    #[\Override]
    protected function buildEqualExpression(array $value): Expression
    {
        [$translationKeyId, $languageCode] = $value;

        return new CompositeExpression(CompositeExpression::TYPE_AND, [
            Criteria::expr()->eq('id', $translationKeyId),
            Criteria::expr()->eq('{language}.code', $languageCode)
        ]);
    }

    #[\Override]
    protected function buildNotEqualExpression(array $value): Expression
    {
        [$translationKeyId, $languageCode] = $value;

        return new CompositeExpression(CompositeExpression::TYPE_OR, [
            Criteria::expr()->neq('id', $translationKeyId),
            Criteria::expr()->neq('{language}.code', $languageCode)
        ]);
    }

    #[\Override]
    protected function parseIdentifier(mixed $value): mixed
    {
        $translationKeyId = TranslationIdUtil::extractTranslationKeyId($value);
        $languageCode = TranslationIdUtil::extractLanguageCode($value);
        if (null === $translationKeyId || null === $languageCode) {
            throw new InvalidFilterValueException(sprintf('The value "%s" is not valid identifier.', $value));
        }

        return [$translationKeyId, $languageCode];
    }
}
