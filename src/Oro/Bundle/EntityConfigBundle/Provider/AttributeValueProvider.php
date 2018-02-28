<?php

namespace Oro\Bundle\EntityConfigBundle\Provider;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

class AttributeValueProvider implements AttributeValueProviderInterface
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
     * @param AttributeFamily $attributeFamily
     * @param array $names
     */
    public function removeAttributeValues(AttributeFamily $attributeFamily, array $names)
    {
        $manager = $this->doctrineHelper->getEntityManagerForClass($attributeFamily->getEntityClass());

        $queryBuilder = $manager->createQueryBuilder();
        $queryBuilder
            ->update($attributeFamily->getEntityClass(), 'entity')
            ->where($queryBuilder->expr()->eq('entity.attributeFamily', ':attributeFamily'))
            ->setParameter('attributeFamily', $attributeFamily)
            ->setParameter('null', null);

        foreach ($names as $name) {
            $queryBuilder->set(QueryBuilderUtil::getField('entity', $name), ':null');
        }

        $queryBuilder->getQuery()->execute();
    }
}
