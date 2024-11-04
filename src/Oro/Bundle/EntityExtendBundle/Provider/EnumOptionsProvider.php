<?php

namespace Oro\Bundle\EntityExtendBundle\Provider;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Cache\EnumTranslationCache;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOptionInterface;
use Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumOptionRepository;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * Provides a way to get enum options.
 */
class EnumOptionsProvider
{
    public function __construct(
        private DoctrineHelper $doctrineHelper,
        private EnumTranslationCache $enumTranslationCache
    ) {
    }

    public function getEnumInternalChoices(string $enumCode): array
    {
        $nonUniqueEnumTranslations = $this->getEnumChoicesWithNonUniqueTranslation($enumCode);
        $choicesByInternalId = [];
        foreach ($nonUniqueEnumTranslations as $optionId => $value) {
            $choicesByInternalId[ExtendHelper::getEnumInternalId($optionId)] = $value;
        }

        return $choicesByInternalId;
    }

    public function getEnumInternalChoicesByCode(string $enumCode): array
    {
        return array_flip($this->getEnumInternalChoices($enumCode));
    }

    public function getEnumChoicesWithNonUniqueTranslation(string $enumCode): array
    {
        return $this->enumTranslationCache->get($enumCode, $this->getEnumOptionRepository());
    }

    /**
     * @return array [enum option name => enum option id, ...]
     */
    public function getEnumChoicesByCode(string $enumCode): array
    {
        $nonUniqueEnumTranslations = $this->getEnumChoicesWithNonUniqueTranslation($enumCode);
        // array_flip() does not retain the data type of values,
        // it will convert string value to integer when it is numeric
        return array_map(
            static function ($data) {
                return (string)$data;
            },
            array_flip($nonUniqueEnumTranslations)
        );
    }

    public function getEnumChoicesWithIdKey(string $enumCode): array
    {
        return $this->getEnumChoicesWithNonUniqueTranslation($enumCode);
    }

    public function getEnumOptionByCode(string $enumCode, string $internalId): EnumOptionInterface
    {
        return $this->doctrineHelper->getEntityReference(
            EnumOption::class,
            ExtendHelper::buildEnumOptionId($enumCode, $internalId)
        );
    }

    public function getDefaultEnumOptionByCode(string $enumCode): ?EnumOptionInterface
    {
        $defaultStatuses = $this->getDefaultEnumOptionsByCode($enumCode);

        return $defaultStatuses ? reset($defaultStatuses) : null;
    }

    public function getDefaultEnumOptions(string $enumCode): array
    {
        return $this->getEnumOptionRepository()->getDefaultValues($enumCode);
    }

    /**
     * @return EnumOptionInterface[]
     */
    public function getDefaultEnumOptionsByCode(string $enumCode): array
    {
        return $this->getDefaultEnumOptions($enumCode);
    }

    private function getEnumOptionRepository(): EnumOptionRepository
    {
        return $this->doctrineHelper->getEntityRepository(EnumOption::class);
    }
}
