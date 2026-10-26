<?php

declare(strict_types=1);

namespace Oro\Bundle\ApiBundle\Processor\EnumOption;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\TranslationBundle\Api\Repository\TranslationDataLoader;
use Oro\Bundle\TranslationBundle\Api\TranslationIdUtil;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Entity\TranslationKey;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Computes the "names" relationship for an enum option.
 */
class ComputeEnumOptionNames implements ProcessorInterface
{
    public function __construct(
        private readonly DoctrineHelper $doctrineHelper,
        private readonly TranslationDataLoader $translationDataLoader
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeLoadedDataContext $context */

        if (!$context->isFieldRequested('names')) {
            return;
        }

        $data = $context->getData();
        $optionId = $data[$context->getResultFieldName('id')];
        if (!$optionId || !\is_string($optionId)) {
            return;
        }

        $data[$context->getResultFieldName('names')] = $this->getNames($optionId, $context);
        $context->setData($data);
    }

    private function getNames(string $optionId, CustomizeLoadedDataContext $context): array
    {
        // The nested relationship config omits fields expected by TranslationDataLoader.
        $config = clone $context->getConfig()->getField('names')->getTargetEntity();
        $config->getOrAddField('languageCode')->setExcluded(false);
        foreach (['translationId', 'translatedValue'] as $fieldName) {
            if (null === $config->getField($fieldName)) {
                $config->addField($fieldName)->setExcluded(true);
            }
        }
        $normalizationContext = $context->getNormalizationContext();
        $data = $this->translationDataLoader->loadData(
            $this->getTranslationDataQueryBuilder($optionId),
            $config,
            $normalizationContext
        );

        $names = $this->translationDataLoader->serializeData($data, $config, $normalizationContext);
        // Translation's own ID processor does not run for these nested rows.
        foreach ($names as $index => $name) {
            $names[$index]['id'] = TranslationIdUtil::buildTranslationId((int)$name['id'], $name['languageCode']);
        }

        return $names;
    }

    private function getTranslationDataQueryBuilder(string $optionId): QueryBuilder
    {
        return $this->doctrineHelper->getEntityRepositoryForClass(TranslationKey::class)
            ->createQueryBuilder('e')
            ->innerJoin(Language::class, 'language', Join::WITH, '1 = 1')
            ->leftJoin(
                Translation::class,
                'translation',
                Join::WITH,
                'translation.translationKey = e AND translation.language = language'
            )
            ->where('e.key = :key AND e.domain = :domain')
            ->setParameter('key', ExtendHelper::buildEnumOptionTranslationKey($optionId))
            ->setParameter('domain', TranslationManager::DEFAULT_DOMAIN);
    }
}
