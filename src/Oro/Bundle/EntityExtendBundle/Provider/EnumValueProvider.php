<?php

namespace Oro\Bundle\EntityExtendBundle\Provider;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Cache\EnumTranslationCache;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumValueRepository;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * Provider for getting enum values
 */
class EnumValueProvider
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var EnumTranslationCache
     */
    protected $enumTranslationCache;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param EnumTranslationCache $enumTranslationCache
     */
    public function __construct(DoctrineHelper $doctrineHelper, EnumTranslationCache $enumTranslationCache)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->enumTranslationCache = $enumTranslationCache;
    }

    /**
     * @param string $enumCode
     * @return array
     */
    public function getEnumChoicesByCode($enumCode)
    {
        return $this->getEnumChoices(ExtendHelper::buildEnumValueClassName($enumCode));
    }

    /**
     * @param string $enumClass = ExtendHelper::buildEnumValueClassName($enumCode);
     * @return array
     */
    public function getEnumChoices($enumClass)
    {
        if (!$this->enumTranslationCache->contains($enumClass)) {
            /** @var EnumValueRepository $repository */
            $repository = $this->doctrineHelper->getEntityRepository($enumClass);
            $values = $repository->getValues();
            $result = [];

            /** @var AbstractEnumValue[] $values */
            foreach ($values as $enum) {
                $result[$enum->getName()] = $enum->getId();
            }
            $this->enumTranslationCache->save($enumClass, $result);
        } else {
            $result = $this->enumTranslationCache->fetch($enumClass);
        }

        return $result;
    }

    /**
     * @param string $enumCode
     * @param string $id
     * @return AbstractEnumValue
     */
    public function getEnumValueByCode($enumCode, $id)
    {
        $enumClass = ExtendHelper::buildEnumValueClassName($enumCode);

        return $this->doctrineHelper->getEntityReference($enumClass, $id);
    }

    /**
     * @param string $enumCode
     * @return AbstractEnumValue[]
     */
    public function getDefaultEnumValuesByCode($enumCode)
    {
        $enumClass = ExtendHelper::buildEnumValueClassName($enumCode);

        /** @var EnumValueRepository $repo */
        $repo = $this->doctrineHelper->getEntityRepository($enumClass);

        return $repo->getDefaultValues();
    }
}
