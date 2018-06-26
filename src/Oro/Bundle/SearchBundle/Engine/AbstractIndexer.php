<?php

namespace Oro\Bundle\SearchBundle\Engine;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr\OrderBy;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\SearchBundle\Query\Mode;

/**
 * Abstract indexer for standard search engine
 */
abstract class AbstractIndexer implements IndexerInterface
{
    const BATCH_SIZE = 1000;

    /** @var ManagerRegistry */
    protected $registry;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ObjectMapper */
    protected $mapper;

    /** @var EntityNameResolver */
    protected $entityNameResolver;

    /**
     * @param ManagerRegistry              $registry
     * @param DoctrineHelper               $doctrineHelper
     * @param ObjectMapper                 $mapper
     * @param EntityNameResolver           $entityNameResolver
     */
    public function __construct(
        ManagerRegistry $registry,
        DoctrineHelper $doctrineHelper,
        ObjectMapper $mapper,
        EntityNameResolver $entityNameResolver
    ) {
        $this->registry = $registry;
        $this->doctrineHelper = $doctrineHelper;
        $this->mapper = $mapper;
        $this->entityNameResolver = $entityNameResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function getClassesForReindex($class = null, array $context = [])
    {
        if (false == $class) {
            return $this->mapper->getEntities([Mode::NORMAL, Mode::WITH_DESCENDANTS]);
        } else {
            $entityNames = (array)$class;

            $mode = $this->mapper->getEntityModeConfig($class);

            if ($mode === Mode::WITH_DESCENDANTS) {
                $entityNames = array_merge($entityNames, $this->mapper->getRegisteredDescendants($class));
            } elseif ($mode === Mode::ONLY_DESCENDANTS) {
                $entityNames = $this->mapper->getRegisteredDescendants($class);
            }

            return array_unique($entityNames);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function reindex($class = null, array $context = [])
    {
        if (false == $class) {
            $entityNames = $this->getClassesForReindex();
            $this->resetIndex();
        } else {
            $entityNames = $this->getClassesForReindex($class);
            $this->resetIndex($entityNames);
        }

        // index data by mapping config
        $recordsCount = 0;

        while ($class = array_shift($entityNames)) {
            $itemsCount = $this->reindexSingleEntity($class);
            $recordsCount += $itemsCount;
        }

        return $recordsCount;
    }

    /**
     * @param string $class
     *
     * @return int
     */
    protected function reindexSingleEntity($class)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->registry->getManagerForClass($class);
        $entityManager->getConnection()->getConfiguration()->setSQLLogger(null);

        $pk = $entityManager->getClassMetadata($class)->getIdentifier();

        $orderingsExpr = new OrderBy();
        foreach ($pk as $fieldName) {
            $orderingsExpr->add('entity.' . $fieldName);
        }

        $queryBuilder = $entityManager->getRepository($class)
            ->createQueryBuilder('entity')
            ->orderBy($orderingsExpr)
        ;
        
        $iterator = new BufferedIdentityQueryResultIterator($queryBuilder);
        $iterator->setBufferSize(static::BATCH_SIZE);

        $itemsCount = 0;
        $entities   = [];

        foreach ($iterator as $entity) {
            $entities[] = $entity;
            $itemsCount++;

            if (0 == $itemsCount % static::BATCH_SIZE) {
                $this->save($entities);
                $entityManager->clear();
                $entities = [];
                gc_collect_cycles();
            }
        }

        if ($itemsCount % static::BATCH_SIZE > 0) {
            $this->save($entities);
            $entityManager->clear();
        }

        return $itemsCount;
    }

    /**
     * Get entity string
     *
     * @param object $entity
     *
     * @return string
     */
    protected function getEntityTitle($entity)
    {
        return $this->entityNameResolver->getName($entity);
    }

    /**
     * @param object|array $entity
     * @return array
     */
    protected function getEntitiesArray($entity)
    {
        if (!$entity) {
            return [];
        }

        return is_array($entity) ? $entity : [$entity];
    }

    /**
     * @return int
     */
    public function getBatchSize()
    {
        return self::BATCH_SIZE;
    }
}
