<?php
namespace Oro\Bundle\SearchBundle\Engine;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr\OrderBy;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SearchBundle\Query\Mode;

abstract class AbstractIndexer implements IndexerInterface
{
    const BATCH_SIZE = 1000;

    /** @var ManagerRegistry */
    protected $registry;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ObjectMapper */
    protected $mapper;

    /**
     * @param ManagerRegistry $registry
     * @param DoctrineHelper  $doctrineHelper
     * @param ObjectMapper    $mapper
     */
    public function __construct(ManagerRegistry $registry, DoctrineHelper $doctrineHelper, ObjectMapper $mapper)
    {
        $this->registry = $registry;
        $this->doctrineHelper = $doctrineHelper;
        $this->mapper = $mapper;
    }

    /**
     * {@inheritdoc}
     */
    public function getClassesForReindex($class = null)
    {
        if (false == $class) {
            return $this->mapper->getEntities([Mode::NORMAL, Mode::WITH_DESCENDANTS]);
        } else {
            $entityNames = [$class];
            
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
    public function reindex($class = null)
    {
        if (false == $class) {
            $this->resetIndex();
            $entityNames = $this->getClassesForReindex();
        } else {
            $entityNames = $this->getClassesForReindex($class);

            foreach ($entityNames as $class) {
                $this->resetIndex($class);
            }
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
        
        $iterator = new BufferedQueryResultIterator($queryBuilder);
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
        $entityClass = ClassUtils::getClass($entity);
        $fields      = $this->mapper->getEntityMapParameter($entityClass, 'title_fields');
        if ($fields) {
            $title = [];
            foreach ($fields as $field) {
                $title[] = $this->mapper->getFieldValue($entity, $field);
            }
        } else {
            $title = [(string) $entity];
        }

        return implode(' ', $title);
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
}
