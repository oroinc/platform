<?php

namespace Oro\Bundle\EntityExtendBundle\Provider;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Cache\EnumTranslationCache;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumValueRepository;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * Provides a way to get enum values.
 */
class EnumValueProvider
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var EnumTranslationCache */
    private $enumTranslationCache;

    public function __construct(DoctrineHelper $doctrineHelper, EnumTranslationCache $enumTranslationCache)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->enumTranslationCache = $enumTranslationCache;
    }

    /**
     * Get only unique enum translations values.
     *
     * @param string $enumClass {@see \Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper::buildEnumValueClassName}
     *
     * @return array [enum value id => enum value name, ...]
     */
    public function getEnumChoices(string $enumClass): array
    {
        $nonUniqueEnumTranslations = $this->getEnumChoicesWithNonUniqueTranslation($enumClass);

        // array_flip() does not retain the data type of values,
        // it will convert string value to integer when it is numeric
        return array_map(
            static function ($data) {
                return (string)$data;
            },
            array_flip($nonUniqueEnumTranslations)
        );
    }

    /**
     * @param string $enumClass {@see \Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper::buildEnumValueClassName}
     *
     * @return array [enum value name => enum value id, ...]
     */
    public function getEnumChoicesWithNonUniqueTranslation(string $enumClass): array
    {
        return $this->enumTranslationCache->get($enumClass, $this->getEnumValueRepository($enumClass));
    }

    /**
     * @param string $enumCode
     *
     * @return array [enum value name => enum value id, ...]
     */
    public function getEnumChoicesByCode(string $enumCode): array
    {
        return $this->getEnumChoices(ExtendHelper::buildEnumValueClassName($enumCode));
    }

    public function getEnumValueByCode(string $enumCode, string $id): AbstractEnumValue
    {
        return $this->doctrineHelper->getEntityReference(ExtendHelper::buildEnumValueClassName($enumCode), $id);
    }

    /**
     * @param string $enumClass {@see \Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper::buildEnumValueClassName}
     *
     * @return AbstractEnumValue|null
     */
    public function getDefaultEnumValue(string $enumClass): ?AbstractEnumValue
    {
        $defaultStatuses = $this->getDefaultEnumValues($enumClass);

        return $defaultStatuses ? reset($defaultStatuses) : null;
    }

    public function getDefaultEnumValueByCode(string $enumCode): ?AbstractEnumValue
    {
        $defaultStatuses = $this->getDefaultEnumValuesByCode($enumCode);

        return $defaultStatuses ? reset($defaultStatuses) : null;
    }

    /**
     * @param string $enumClass {@see \Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper::buildEnumValueClassName}
     *
     * @return AbstractEnumValue[]
     */
    public function getDefaultEnumValues(string $enumClass): array
    {
        return $this->getEnumValueRepository($enumClass)->getDefaultValues();
    }

    /**
     * @param string $enumCode
     *
     * @return AbstractEnumValue[]
     */
    public function getDefaultEnumValuesByCode(string $enumCode): array
    {
        return $this->getDefaultEnumValues(ExtendHelper::buildEnumValueClassName($enumCode));
    }

    private function getEnumValueRepository(string $enumClass): EnumValueRepository
    {
        return $this->doctrineHelper->getEntityRepository($enumClass);
    }
}
