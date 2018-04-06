<?php

namespace Oro\Bundle\ImportExportBundle\Converter;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\ImportExportBundle\Exception\LogicException;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

class RelationCalculator implements RelationCalculatorInterface
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var FieldHelper
     */
    protected $fieldHelper;

    /**
     * @param ManagerRegistry $registry
     * @param FieldHelper $fieldHelper
     */
    public function __construct(
        ManagerRegistry $registry,
        FieldHelper $fieldHelper
    ) {
        $this->registry = $registry;
        $this->fieldHelper = $fieldHelper;
    }

    /**
     * @param string $entityName
     * @param string $fieldName
     * @return int
     * @throws LogicException
     */
    public function getMaxRelatedEntities($entityName, $fieldName)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->registry->getManagerForClass($entityName);
        $entityMetadata = $entityManager->getClassMetadata($entityName);
        $entityIdentifier = $entityMetadata->getIdentifierFieldNames();
        $entityIdentifier = reset($entityIdentifier);

        $relationEntityName = $this->getRelationEntityName($entityName, $fieldName);
        if (!$relationEntityName) {
            throw new LogicException(sprintf('%s:%s is not multiple relation field', $entityName, $fieldName));
        }

        $relationMetadata = $entityManager->getClassMetadata($relationEntityName);
        $relationIdentifier =  $relationMetadata->getIdentifierFieldNames();
        $relationIdentifier = reset($relationIdentifier);

        $queryBuilder = $entityManager->createQueryBuilder()
            ->select('COUNT(relation.' . $relationIdentifier . ') as maxCount')
            ->from($entityName, 'entity')
            ->join(QueryBuilderUtil::getField('entity', $fieldName), 'relation')
            ->groupBy('entity.' . $entityIdentifier)
            ->orderBy('maxCount', 'DESC')
            ->setMaxResults(1);

        $query = $queryBuilder->getQuery();
        $result = $query->getOneOrNullResult(Query::HYDRATE_ARRAY);

        return !empty($result['maxCount']) ? (int)$result['maxCount'] : 0;
    }

    /**
     * @param string $entityName
     * @param string $fieldName
     * @return string|null
     */
    protected function getRelationEntityName($entityName, $fieldName)
    {
        $fields = $this->fieldHelper->getFields($entityName, true);
        foreach ($fields as $field) {
            if ($field['name'] == $fieldName && $this->fieldHelper->isMultipleRelation($field)) {
                return $field['related_entity_name'];
            }
        }

        return null;
    }
}
