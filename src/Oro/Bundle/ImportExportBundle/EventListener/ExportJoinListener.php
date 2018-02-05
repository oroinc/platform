<?php

namespace Oro\Bundle\ImportExportBundle\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ImportExportBundle\Event\AfterEntityPageLoadedEvent;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * This listener join entity associations for decreasing count of queries during entity export
 */
class ExportJoinListener
{
    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param AfterEntityPageLoadedEvent $event
     */
    public function onAfterEntityPageLoaded(AfterEntityPageLoadedEvent $event)
    {
        $rows = $event->getRows();

        if (empty($rows)) {
            return;
        }

        $entityName = ClassUtils::getClass($rows[0]);
        $ids = $this->getEntityIds($rows, $entityName);

        if ($ids) {
            $modifiedRows = $this->getModifiedRows($entityName, $ids);
            $event->setRows($modifiedRows);
        }
    }

    /**
     * @param array $rows
     * @param string $entityName
     * @return array
     */
    private function getEntityIds(array $rows, string $entityName)
    {
        $identifier = $this->getEntityIdentifier($entityName);

        return array_map(
            function ($object) use ($identifier) {
                return  PropertyAccess::createPropertyAccessor()->getValue($object, $identifier) ;
            },
            $rows
        );
    }

    /**
     * @param string $entityName
     * @return string
     */
    private function getEntityIdentifier(string $entityName)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->registry->getManagerForClass($entityName);
        $metadata = $entityManager->getClassMetadata($entityName);

        return $metadata->getSingleIdentifierFieldName();
    }

    /**
     * @param string $entityName
     * @param array $ids
     * @return array
     */
    private function getModifiedRows(string $entityName, array $ids)
    {
        $queryBuilder = $this->createSourceEntityQueryBuilderWithAssociations($entityName, $ids);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param string $entityName
     * @param array $ids
     *
     * @return QueryBuilder
     */
    private function createSourceEntityQueryBuilderWithAssociations(string $entityName, array $ids)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->registry->getManagerForClass($entityName);

        $qb = $entityManager->getRepository($entityName)->createQueryBuilder('entity');

        $metadata = $entityManager->getClassMetadata($entityName);

        $this->joinAssociations($qb, $metadata);

        $identifierNames = $metadata->getIdentifierFieldNames();
        foreach ($identifierNames as $fieldName) {
            $qb->orderBy('entity.' . $fieldName, 'ASC');
        }

        $identifierName = 'entity.' . current($identifierNames);

        $qb->andWhere($qb->expr()->in($identifierName, ':ids'))
            ->setParameter('ids', $ids);

        return $qb;
    }

    /**
     * @param QueryBuilder $qb
     * @param ClassMetadata $metadata
     */
    private function joinAssociations(QueryBuilder $qb, ClassMetadata $metadata)
    {
        $associations = array_keys($metadata->getAssociationMappings());
        foreach ($associations as $fieldName) {
            $alias = '_' . $fieldName;
            $qb->addSelect($alias);
            $qb->leftJoin('entity.' . $fieldName, $alias);
        }
    }
}
