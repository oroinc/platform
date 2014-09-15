<?php

namespace Oro\Bundle\SearchBundle\Engine;

use JMS\JobQueueBundle\Entity\Job;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\SearchBundle\Entity\Repository\SearchIndexRepository;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result\Item as ResultItem;
use Oro\Bundle\SearchBundle\Entity\Item;

class Orm extends AbstractEngine
{
    /**
     * @var SearchIndexRepository
     */
    protected $indexRepository;

    /**
     * @var ObjectMapper
     */
    protected $mapper;

    /**
     * @var array
     */
    protected $drivers = array();

    /**
     * @var bool
     */
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
    public function reindex($class = null)
    {
        if (null === $class) {
            $this->clearAllSearchIndexes();
            $entityNames = $this->mapper->getEntities();
        } else {
            $this->clearSearchIndexForEntity($class);
            $entityNames = array($class);
        }

        // index data by mapping config
        $recordsCount = 0;

        while ($class = array_shift($entityNames)) {
            $itemsCount    = $this->reindexSingleEntity($class);
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
        $existingItems = $this->getIndexRepository()->getItemsForEntities($entities);

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
        $existingItems = $this->getIndexRepository()->getItemsForEntities($entities);

        $hasSavedEntities = false;
        foreach ($entities as $entity) {
            $data = $this->mapper->mapObject($entity);
            if (empty($data)) {
                continue;
            }

            $class = $this->doctrineHelper->getEntityClass($entity);
            $id = $this->doctrineHelper->getSingleEntityIdentifier($entity);

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
        $results = array();
        $searchResults = $this->getIndexRepository()->search($query);
        if (($query->getMaxResults() > 0 || $query->getFirstResult() > 0)) {
            $recordsCount = $this->getIndexRepository()->getRecordsCount($query);
        } else {
            $recordsCount = count($searchResults);
        }
        if ($searchResults) {
            foreach ($searchResults as $item) {
                if (is_array($item)) {
                    $item = $item['item'];
                }
                /** @var $item Item  */
                $results[] = new ResultItem(
                    $this->registry->getManagerForClass($item->getEntity()),
                    $item->getEntity(),
                    $item->getRecordId(),
                    $item->getTitle(),
                    null,
                    $item->getRecordText(),
                    $this->mapper->getEntityConfig($item->getEntity())
                );
            }
        }

        return array(
            'results' => $results,
            'records_count' => $recordsCount
        );
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
        if (!$this->indexRepository) {
            $this->indexRepository = $this->registry->getRepository('OroSearchBundle:Item');
            $this->indexRepository->setDriversClasses($this->drivers);
        }

        return $this->indexRepository;
    }

    /**
     * Clear search all search indexes or for custom entity
     *
     * @param string $entityName
     */
    protected function clearSearchIndexForEntity($entityName)
    {
        $itemsCount    = 0;
        $entityManager = $this->registry->getManagerForClass('OroSearchBundle:Item');
        $queryBuilder  = $this->getIndexRepository()->createQueryBuilder('item')
            ->where('item.entity = :entity')
            ->setParameter('entity', $entityName);

        $iterator = new BufferedQueryResultIterator($queryBuilder);
        $iterator->setBufferSize(static::BATCH_SIZE);

        foreach ($iterator as $entity) {
            $itemsCount++;
            $entityManager->remove($entity);

            if (0 == $itemsCount % static::BATCH_SIZE) {
                $entityManager->flush();
                $entityManager->clear();
            }
        }

        if ($itemsCount % static::BATCH_SIZE > 0) {
            $entityManager->flush();
            $entityManager->clear();
        }
    }

    /**
     * Truncate search tables
     */
    protected function clearAllSearchIndexes()
    {
        $this->getIndexRepository()->truncateIndex();
    }
}
