<?php

namespace Oro\Bundle\EntityExtendBundle\Provider;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumValueRepository;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class EnumValueProvider
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
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
        /** @var EnumValueRepository $repository */
        $repository = $this->doctrineHelper->getEntityRepository($enumClass);
        $values = $repository->getValues();
        $result = [];
        /** @var AbstractEnumValue $enum */
        foreach ($values as $enum) {
            $result[$enum->getId()] = $enum->getName();
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
        $repo = $this->doctrineHelper->getEntityRepository($enumClass);

        return $repo->getDefaultValues();
    }
}
