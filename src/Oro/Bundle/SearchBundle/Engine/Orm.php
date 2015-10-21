<?php

namespace Oro\Bundle\SearchBundle\Engine;

use JMS\JobQueueBundle\Entity\Job;

use Oro\Bundle\EntityBundle\ORM\OroEntityManager;

use Oro\Bundle\SearchBundle\Entity\Item;
use Oro\Bundle\SearchBundle\Entity\Repository\SearchIndexRepository;
use Oro\Bundle\SearchBundle\Query\Mode;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result\Item as ResultItem;

class Orm extends AbstractEngine
{
    /** @var SearchIndexRepository */
    protected $indexRepository;

    /** @var ObjectMapper */
    protected $mapper;

    /** @var array */
    protected $drivers = [];

    /** @var bool */
    protected $needFlush = true;

    /**
     * @param array $drivers
     */
    public function setDrivers(array $drivers)
    {
        $this->drivers = $drivers;
    }

    /**
     * {@inheritdoc}
     */
    public function reindex($class = null, $offset = null, $limit = null)
    {
        if (null === $class) {
            $this->clearAllSearchIndexes();
            $entityNames = $this->mapper->getEntities([Mode::NORMAL, Mode::WITH_DESCENDANTS]);
        } else {
            $entityNames = [$class];
            $mode        = $this->mapper->getEntityModeConfig($class);
            if ($mode === Mode::WITH_DESCENDANTS) {
                $entityNames = array_merge($entityNames, $this->mapper->getRegisteredDescendants($class));
            } elseif ($mode === Mode::ONLY_DESCENDANTS) {
                $entityNames = $this->mapper->getRegisteredDescendants($class);
            }

            if ((null === $offset && null === $limit) || ($offset === 0 && $limit)) {
                foreach ($entityNames as $class) {
                    $this->clearSearchIndexForEntity($class);
                }
            }
        }

        // index data by mapping config
        $recordsCount = 0;

        while ($class = array_shift($entityNames)) {
            $itemsCount = $this->reindexSingleEntity($class, $offset, $limit);
            $recordsCount += $itemsCount;
        }

        return $recordsCount;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($entity, $realTime = true)
    {
        $entities = $this->getEntitiesArray($entity);
        if (!$entities) {
            return false;
        }

        if (!$realTime) {
            $this->scheduleIndexation($entities);

            return true;
        }

        $itemEntityManager = $this->registry->getManagerForClass('OroSearchBundle:Item');
        $existingItems     = $this->getIndexRepository()->getItemsForEntities($entities);

        $hasDeletedEntities = !empty($existingItems);
        foreach ($existingItems as $items) {
            foreach ($items as $item) {
                $itemEntityManager->remove($item);
            }
        }

        if ($hasDeletedEntities && $this->needFlush) {
            $this->flush();
        }

        return $hasDeletedEntities;
    }

    /**
     * {@inheritdoc}
     */
    public function save($entity, $realTime = true)
    {
        $entities = $this->getEntitiesArray($entity);
        if (!$entities) {
            return false;
        }

        if (!$realTime) {
            $this->scheduleIndexation($entities);

            return true;
        }

        $hasSavedEntities = $this->saveItemData($entities);

        if ($hasSavedEntities && $this->needFlush) {
            $this->flush();
        }

        return $hasSavedEntities;
    }

    /**
     * @param array $entities
     * @return bool
     */
    protected function saveItemData(array $entities)
    {
        $itemEntityManager = $this->registry->getManagerForClass('OroSearchBundle:Item');
        $existingItems     = $this->getIndexRepository()->getItemsForEntities($entities);

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

            $item->setTitle($this->getEntityTitle($entity))
                ->setChanged(false)
                ->saveItemData($data);

            $itemEntityManager->persist($item);

            $hasSavedEntities = true;
        }

        return $hasSavedEntities;
    }

    /**
     * @param bool $needFlush
     */
    public function setNeedFlush($needFlush)
    {
        $this->needFlush = $needFlush;
    }

    /**
     * Flush entity manager entities
     */
    public function flush()
    {
        $this->registry->getManager()->flush();
    }

    /**
     * {@inheritdoc}
     */
    protected function doSearch(Query $query)
    {
        $results       = [];
        $searchResults = $this->getIndexRepository()->search($query);
        if (($query->getCriteria()->getMaxResults() > 0 || $query->getCriteria()->getFirstResult() > 0)) {
            $recordsCount = $this->getIndexRepository()->getRecordsCount($query);
        } else {
            $recordsCount = count($searchResults);
        }
        if ($searchResults) {
            foreach ($searchResults as $item) {
                if (is_array($item)) {
                    $item = $item['item'];
                }

                /**
                 * Search result can contains duplicates and we can not use HYDRATE_OBJECT because of performance issue.
                 * @todo: update after fix BAP-7166. Remove check for existing result.
                 */
                $id = $item['id'];
                if (isset($results[$id])) {
                    continue;
                }

                $results[$id] = new ResultItem(
                    $this->registry->getManagerForClass($item['entity']),
                    $item['entity'],
                    $item['recordId'],
                    $item['title'],
                    null,
                    $this->mapper->getEntityConfig($item['entity'])
                );
            }
        }

        return [
            'results'       => $results,
            'records_count' => $recordsCount
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function scheduleIndexation($entity)
    {
        $entityManager = $this->registry->getManagerForClass('JMSJobQueueBundle:Job');

        $jobs = $this->createQueueJobs($entity);
        if ($jobs) {
            foreach ($jobs as $job) {
                $entityManager->persist($job);
            }

            if ($this->needFlush) {
                $entityManager->flush();
            }
        }
    }

    /**
     * Get search index repository
     *
     * @return SearchIndexRepository
     */
    protected function getIndexRepository()
    {
        $this->indexRepository = $this->registry->getRepository('OroSearchBundle:Item');
        $this->indexRepository->setDriversClasses($this->drivers);

        return $this->indexRepository;
    }

    /**
     * Clear search all search indexes or for custom entity
     *
     * @param string $entityName
     */
    protected function clearSearchIndexForEntity($entityName)
    {
        /** @var OroEntityManager $em */
        $em = $this->registry->getManager();

        $query = <<<EOF
DELETE FROM oro_search_index_integer  WHERE item_id IN (SELECT DISTINCT id FROM oro_search_item WHERE entity = ?);
DELETE FROM oro_search_index_datetime WHERE item_id IN (SELECT DISTINCT id FROM oro_search_item WHERE entity = ?);
DELETE FROM oro_search_index_decimal  WHERE item_id IN (SELECT DISTINCT id FROM oro_search_item WHERE entity = ?);
DELETE FROM oro_search_index_text     WHERE item_id IN (SELECT DISTINCT id FROM oro_search_item WHERE entity = ?);
DELETE FROM oro_search_item           WHERE entity = ?;
EOF;
        $em->getConnection()->executeQuery(
            $query,
            [$entityName, $entityName, $entityName, $entityName, $entityName],
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
}
