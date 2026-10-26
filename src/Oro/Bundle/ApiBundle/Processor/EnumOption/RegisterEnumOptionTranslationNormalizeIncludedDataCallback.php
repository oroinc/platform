<?php

declare(strict_types=1);

namespace Oro\Bundle\ApiBundle\Processor\EnumOption;

use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Processor\Shared\JsonApi\NormalizeIncludedData;
use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\TranslationBundle\Api\Model\TranslationUpdate;
use Oro\Bundle\TranslationBundle\Api\TranslationIdUtil;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Entity\TranslationKey;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Registers a callback function that should be used to load and normalize existing translations
 * from an enum option's included data for editing.
 * For example, an included translation with ID "1253-de_DE" and meta.update=true
 * is resolved by NormalizeIncludedData to
 * TranslationUpdate("1253-de_DE", 1253, "de_DE", translation row ID, current value).
 */
class RegisterEnumOptionTranslationNormalizeIncludedDataCallback implements ProcessorInterface
{
    public function __construct(
        private readonly DoctrineHelper $doctrineHelper
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var SingleItemContext|FormContext $context */

        NormalizeIncludedData::registerNormalizeIncludedDataCallback(
            $context,
            TranslationKey::class,
            function (mixed $entityIdOrCriteria) use ($context): ?TranslationUpdate {
                return \is_string($entityIdOrCriteria)
                    ? $this->findTranslation($entityIdOrCriteria, (string)$context->getId())
                    : null;
            }
        );
    }

    private function findTranslation(string $translationId, string $optionId): ?TranslationUpdate
    {
        $keyId = TranslationIdUtil::extractTranslationKeyId($translationId);
        $languageCode = TranslationIdUtil::extractLanguageCode($translationId);
        if (null === $keyId || null === $languageCode) {
            return null;
        }

        $translation = $this->doctrineHelper->createQueryBuilder(Language::class, 'language')
            ->select('translation.id AS translationId, translation.value AS translatedValue')
            ->innerJoin(
                TranslationKey::class,
                'translationKey',
                'WITH',
                'translationKey.id = :keyId AND translationKey.key = :key AND translationKey.domain = :domain'
            )
            ->leftJoin(
                Translation::class,
                'translation',
                'WITH',
                'translation.language = language AND translation.translationKey = translationKey'
            )
            ->where('language.code = :languageCode')
            ->setParameter('keyId', $keyId)
            ->setParameter('key', ExtendHelper::buildEnumOptionTranslationKey($optionId))
            ->setParameter('domain', TranslationManager::DEFAULT_DOMAIN)
            ->setParameter('languageCode', $languageCode)
            ->getQuery()
            ->getOneOrNullResult();
        if (null === $translation) {
            return null;
        }

        return new TranslationUpdate(
            $translationId,
            $keyId,
            $languageCode,
            $translation['translationId'],
            $translation['translatedValue']
        );
    }
}
