<?php

namespace Oro\Bundle\SearchBundle\Engine;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Security\Core\Util\ClassUtils;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;

use JMS\JobQueueBundle\Entity\Job;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SearchBundle\Entity\Repository\SearchIndexRepository;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result\Item as ResultItem;
use Oro\Bundle\SearchBundle\Entity\Item;

class Orm extends AbstractEngine
{
    const BATCH_SIZE = 1000;

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
    protected $drivers;

    /**
     * @var bool
     */
    protected $needFlush = true;

    public function __construct(
        ManagerRegistry $registry,
        EventDispatcher $dispatcher,
        DoctrineHelper $doctrineHelper,
        ObjectMapper $mapper,
        array $drivers,
        $logQueries
    ) {
        parent::__construct($registry, $dispatcher, $doctrineHelper, $mapper, $logQueries);

        $this->drivers = $drivers;
    }

    /**
     * Reload search index
     *
     * @return int Count of index records
     */
    public function reindex()
    {
        // clear old index
        $this->clearSearchIndex();

        // index data by mapping config
        $recordsCount = 0;
        $entities     = $this->mapper->getEntities();

        while ($entityName = array_shift($entities)) {
            $itemsCount    = $this->reindexSingleEntity($entityName);
            $recordsCount += $itemsCount;
        }

        return $recordsCount;
    }

    /**
     * @param string $entityName
     * @return int
     */
    protected function reindexSingleEntity($entityName)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->registry->getRepository($entityName);
        $queryBuilder = $entityManager->createQueryBuilder('entity');
        $iterator = new BufferedQueryResultIterator($queryBuilder);
        $iterator->setBufferSize(self::BATCH_SIZE);

        $itemsCount = 0;
        $entities = array();

        foreach ($iterator as $entity) {
            $entities[] = $entity;
            $itemsCount++;

            if (0 == $itemsCount % self::BATCH_SIZE) {
                $this->save($entities, true);
                $entities[] = array();
            }
        }

        if ($itemsCount % self::BATCH_SIZE > 0) {
            $this->save($entities, true);
            $entities[] = array();
        }

        return $itemsCount;
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

        $entityManager = $this->registry->getManagerForClass('OroSearchBundle:Item');

        $hasDeletedEntities = false;
        foreach ($entities as $entity) {
            $item = $this->getIndexRepository()->findOneBy(
                array(
                    'entity'   => ClassUtils::getRealClass($entity),
                    'recordId' => $this->doctrineHelper->getSingleEntityIdentifier($entity),
                )
            );

            if ($item) {
                $entityManager->remove($item);
                $hasDeletedEntities = true;
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

        $hasSavedEntities = false;
        foreach ($entities as $entity) {
            if ($this->saveEntity($entity)) {
                $hasSavedEntities = true;
            }
        }

        if ($hasSavedEntities && $this->needFlush) {
            $this->flush();
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
     * @param object $entity
     * @return bool
     */
    protected function saveEntity($entity)
    {
        $data = $this->mapper->mapObject($entity);
        if (empty($data)) {
            return false;
        }

        $class = ClassUtils::getRealClass($entity);
        $id = $this->doctrineHelper->getSingleEntityIdentifier($entity);

        $item = null;
        if ($id) {
            $item = $this->getIndexRepository()->findOneBy(
                array(
                    'entity'   => $class,
                    'recordId' => $id
                )
            );
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

        $this->registry->getManagerForClass('OroSearchBundle:Item')->persist($item);

        return true;
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
        foreach ($jobs as $job) {
            $entityManager->persist($job);
        }

        if ($jobs && $this->needFlush) {
            $entityManager->flush();
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
     * Truncate search tables
     */
    protected function clearSearchIndex()
    {
        /** @var Connection $connection */
        $connection = $this->registry->getConnection();
        $dbPlatform = $connection->getDatabasePlatform();
        $connection->beginTransaction();
        try {
            $connection->query('SET FOREIGN_KEY_CHECKS=0');
            $this->truncate($dbPlatform, $connection, 'OroSearchBundle:Item');
            $this->truncate($dbPlatform, $connection, 'OroSearchBundle:IndexDecimal');
            $this->truncate($dbPlatform, $connection, 'OroSearchBundle:IndexText');
            $this->truncate($dbPlatform, $connection, 'OroSearchBundle:IndexInteger');
            $this->truncate($dbPlatform, $connection, 'OroSearchBundle:IndexDatetime');
            $connection->query('SET FOREIGN_KEY_CHECKS=1');
            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollback();
        }
    }

    /**
     * Truncate query for table
     *
     * @param AbstractPlatform $dbPlatform
     * @param Connection $connection
     * @param string $entityName
     */
    protected function truncate(AbstractPlatform $dbPlatform, Connection $connection, $entityName)
    {
        /** @var ClassMetadata $metadata */
        $metadata = $this->registry->getManagerForClass($entityName)->getClassMetadata($entityName);
        $query = $dbPlatform->getTruncateTableSql($metadata->getTableName());
        $connection->executeUpdate($query);
    }
}
