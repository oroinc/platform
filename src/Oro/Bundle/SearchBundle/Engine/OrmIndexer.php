<?php
namespace Oro\Bundle\SearchBundle\Engine;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\ORM\OroEntityManager;
use Oro\Bundle\SearchBundle\Engine\Orm\DbalStorer;
use Oro\Bundle\SearchBundle\Entity\Item;
use Oro\Bundle\SearchBundle\Entity\Repository\SearchIndexRepository;
use Oro\Bundle\SearchBundle\Resolver\EntityTitleResolverInterface;

class OrmIndexer extends AbstractIndexer
{
    /** @var array */
    protected $drivers = [];
    
    /** @var SearchIndexRepository */
    private $indexRepository;

    /** @var OroEntityManager */
    private $indexManager;

    /** @var DbalStorer */
    protected $dbalStorer;

    /**
     * @param ManagerRegistry              $registry
     * @param DoctrineHelper               $doctrineHelper
     * @param ObjectMapper                 $mapper
     * @param EntityTitleResolverInterface $entityTitleResolver
     * @param DbalStorer                   $dbalStorer
     */
    public function __construct(
        ManagerRegistry $registry,
        DoctrineHelper $doctrineHelper,
        ObjectMapper $mapper,
        EntityTitleResolverInterface $entityTitleResolver,
        DbalStorer $dbalStorer
    ) {
        parent::__construct($registry, $doctrineHelper, $mapper, $entityTitleResolver);
        $this->dbalStorer = $dbalStorer;
    }

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
    public function save($entity, array $context = [])
    {
        $entities = $this->getEntitiesArray($entity);
        if (false == $entities) {
            return false;
        }

        $hasSavedEntities = $this->saveItemData($entities);

        if ($hasSavedEntities) {
            $this->getIndexManager()->getConnection()->transactional(function () {
                $this->dbalStorer->store();
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

        $existingItems = $this->getIndexRepository()->getItemsForEntities($entities);

        $hasDeletedEntities = !empty($existingItems);
        foreach ($existingItems as $items) {
            foreach ($items as $item) {
                $this->getIndexManager()->remove($item);
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
        if (false == $class) {
            $this->clearAllSearchIndexes();
        } else {
            $this->clearSearchIndexForEntity($class);
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

            $item->setTitle($this->getEntityTitle($entity))
                ->setChanged(false)
                ->saveItemData($data);

            $this->dbalStorer->addItem($item);

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
        $this->indexRepository->setDriversClasses($this->drivers);
        $this->indexRepository->setRegistry($this->registry);

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
