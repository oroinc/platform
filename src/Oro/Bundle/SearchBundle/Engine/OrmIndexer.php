<?php

namespace Oro\Bundle\SearchBundle\Engine;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\ORM\OroEntityManager;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\SearchBundle\Entity\Item;
use Oro\Bundle\SearchBundle\Entity\Repository\SearchIndexRepository;
use Oro\Bundle\SearchBundle\Query\Query;

/**
 * Performs search indexation (save and delete) for ORM engine at standard search index
 */
class OrmIndexer extends AbstractIndexer
{
    /** @var SearchIndexRepository */
    private $indexRepository;

    /** @var OroEntityManager */
    private $indexManager;

    /**
     * @param ManagerRegistry $registry
     * @param DoctrineHelper $doctrineHelper
     * @param ObjectMapper $mapper
     * @param EntityNameResolver $entityNameResolver
     */
    public function __construct(
        ManagerRegistry $registry,
        DoctrineHelper $doctrineHelper,
        ObjectMapper $mapper,
        EntityNameResolver $entityNameResolver
    ) {
        parent::__construct($registry, $doctrineHelper, $mapper, $entityNameResolver);
    }

    /**
     * {@inheritdoc}
     */
    public function save($entity, array $context = [])
    {
        $entities = $this->getEntitiesArray($entity);
        if (!$entities) {
            return false;
        }

        $hasSavedEntities = $this->saveItemData($entities);

        if ($hasSavedEntities) {
            $this->getIndexManager()->getConnection()->transactional(function () {
                $this->getIndexRepository()->flushWrites();
                $this->getIndexManager()->clear();
            });
        }

        return $hasSavedEntities;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($entity, array $context = [])
    {
        $entities = $this->getEntitiesArray($entity);
        if (!$entities) {
            return false;
        }
        $sortedEntitiesData = [];
        foreach ($entities as $entity) {
            if (!$this->doctrineHelper->isManageableEntity($entity)) {
                continue;
            }
            $entityClass = $this->doctrineHelper->getEntityClass($entity);
            $sortedEntitiesData[$entityClass][] = $this->doctrineHelper->getSingleEntityIdentifier($entity);
        }

        $existingItems = $this->getIndexRepository()->getItemsForEntities($entities);
        $hasDeletedEntities = !empty($existingItems);
        foreach ($sortedEntitiesData as $entityClass => $entityIds) {
            $batches = array_chunk($entityIds, $this->getBatchSize());
            foreach ($batches as $batch) {
                $this->getIndexRepository()->removeEntities($batch, $entityClass);
            }
        }

        if ($hasDeletedEntities) {
            $this->flush();
        }

        return $hasDeletedEntities;
    }

    /**
     * {@inheritdoc}
     */
    public function resetIndex($class = null, array $context = [])
    {
        if (null === $class) {
            $this->clearAllSearchIndexes();
        } else {
            $resetClasses = (array)$class;
            foreach ($resetClasses as $resetClass) {
                $this->clearSearchIndexForEntity($resetClass);
            }
        }
    }

    /**
     * @param array $entities
     * @return bool
     */
    protected function saveItemData(array $entities)
    {
        $existingItems = $this->getIndexRepository()->getItemsForEntities($entities);

        $hasSavedEntities = false;
        foreach ($entities as $entity) {
            $data = $this->mapper->mapObject($entity);
            if (empty($data)) {
                continue;
            }

            $class = $this->doctrineHelper->getEntityClass($entity);
            $id    = $this->doctrineHelper->getSingleEntityIdentifier($entity);

            $item = null;
            if ($id && !empty($existingItems[$class][$id])) {
                $item = $existingItems[$class][$id];
            }

            if (!$item) {
                $item   = new Item();
                $config = $this->mapper->getEntityConfig($class);
                $alias  = $config ? $config['alias'] : $class;

                $item->setEntity($class)
                    ->setRecordId($id)
                    ->setAlias($alias);
            }

            if (isset($data[Query::TYPE_DECIMAL][self::WEIGHT_FIELD])) {
                $item->setWeight($data[Query::TYPE_DECIMAL][self::WEIGHT_FIELD]);
                unset($data[Query::TYPE_DECIMAL][self::WEIGHT_FIELD]);
            } else {
                $item->setWeight(1);
            }

            $item->setTitle($this->getEntityTitle($entity))
                ->setChanged(false)
                ->saveItemData($data);

            $this->getIndexRepository()->writeItem($item);

            $hasSavedEntities = true;
        }

        return $hasSavedEntities;
    }

    /**
     * Clear search all search indexes or for custom entity
     *
     * @param string $class
     */
    protected function clearSearchIndexForEntity($class)
    {
        $query = <<<EOF
DELETE FROM oro_search_index_integer  WHERE item_id IN (SELECT DISTINCT id FROM oro_search_item WHERE entity = ?);
DELETE FROM oro_search_index_datetime WHERE item_id IN (SELECT DISTINCT id FROM oro_search_item WHERE entity = ?);
DELETE FROM oro_search_index_decimal  WHERE item_id IN (SELECT DISTINCT id FROM oro_search_item WHERE entity = ?);
DELETE FROM oro_search_index_text     WHERE item_id IN (SELECT DISTINCT id FROM oro_search_item WHERE entity = ?);
DELETE FROM oro_search_item           WHERE entity = ?;
EOF;

        $this->getIndexManager()->getConnection()->executeQuery(
            $query,
            [$class, $class, $class, $class, $class],
            [\PDO::PARAM_STR, \PDO::PARAM_STR, \PDO::PARAM_STR, \PDO::PARAM_STR, \PDO::PARAM_STR]
        );
    }

    /**
     * Truncate search tables
     */
    protected function clearAllSearchIndexes()
    {
        $this->getIndexRepository()->truncateIndex();
    }

    /**
     * Flush entity manager entities
     */
    protected function flush()
    {
        $this->getIndexManager()->flush();
        $this->getIndexManager()->clear();
    }

    /**
     * Get search index repository
     *
     * @return SearchIndexRepository
     */
    protected function getIndexRepository()
    {
        if ($this->indexRepository) {
            return $this->indexRepository;
        }

        $this->indexRepository = $this->getIndexManager()->getRepository('OroSearchBundle:Item');

        return $this->indexRepository;
    }

    /**
     * Get search index repository
     *
     * @return OroEntitymanager
     */
    protected function getIndexManager()
    {
        if ($this->indexManager) {
            return $this->indexManager;
        }

        $this->indexManager = $this->registry->getManagerForClass('OroSearchBundle:Item');

        return $this->indexManager;
    }
}
