<?php

namespace Oro\Bundle\SearchBundle\Engine;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\ORM\OroEntityManager;
use Oro\Bundle\SearchBundle\Entity\Item;
use Oro\Bundle\SearchBundle\Entity\Repository\SearchIndexRepository;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result\Item as ResultItem;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Orm extends AbstractEngine
{
    const ENGINE_NAME = 'orm';

    /** @var SearchIndexRepository */
    private $indexRepository;

    /** @var OroEntityManager */
    private $indexManager;

    /** @var ObjectMapper */
    protected $mapper;

    /** @var array */
    protected $drivers = [];

    /**
     * @param ManagerRegistry          $registry
     * @param ObjectMapper             $mapper
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        ManagerRegistry $registry,
        ObjectMapper $mapper,
        EventDispatcherInterface $eventDispatcher
    ) {
        parent::__construct($registry, $eventDispatcher);

        $this->mapper = $mapper;
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
                $originalItem = $item;
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
                    $this->mapper->mapSelectedData($query, $originalItem),
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
