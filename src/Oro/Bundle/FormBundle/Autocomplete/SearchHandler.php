<?php

namespace Oro\Bundle\FormBundle\Autocomplete;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * The main implementation of SearchHandlerInterface.
 * Search entities by given string.
 */
class SearchHandler implements SearchHandlerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var Indexer
     */
    protected $indexer;

    /**
     * @var EntityRepository
     */
    protected $entityRepository;

    /**
     * @var string
     */
    protected $entityName;

    /**
     * @var string
     */
    protected $entitySearchAlias;

    /**
     * @var string
     */
    protected $idFieldName;

    /**
     * @var array
     */
    protected $properties;

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var AclHelper $aclHelper
     */
    protected $aclHelper;

    /**
     * @var PropertyAccessorInterface
     */
    protected $propertyAccessor;

    /**
     * @param string $entityName
     * @param array  $properties
     */
    public function __construct($entityName, array $properties)
    {
        $this->entityName = $entityName;
        $this->properties = $properties;
    }

    /**
     * {@inheritdoc}
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @throws \RuntimeException
     */
    public function initSearchIndexer(Indexer $indexer, SearchMappingProvider $mappingProvider)
    {
        $this->indexer = $indexer;
        $entityAlias = $mappingProvider->getEntityAlias($this->entityName);
        if (!$entityAlias) {
            throw new \RuntimeException(sprintf('Cannot init search alias for entity "%s".', $this->entityName));
        }
        $this->entitySearchAlias = $entityAlias;
    }

    /**
     * @throws \RuntimeException
     */
    public function initDoctrinePropertiesByManagerRegistry(ManagerRegistry $managerRegistry)
    {
        $objectManager = $managerRegistry->getManagerForClass($this->entityName);
        if (!$objectManager instanceof ObjectManager) {
            throw new \RuntimeException(
                sprintf(
                    'Object manager for "%s" expected to be an instance of "%s".',
                    $this->entityName,
                    'Doctrine\ORM\ObjectManager'
                )
            );
        }
        $this->initDoctrinePropertiesByEntityManager($objectManager);
    }

    public function initDoctrinePropertiesByEntityManager(ObjectManager $objectManager)
    {
        /** @var $metadata \Doctrine\ORM\Mapping\ClassMetadata */
        $metadata = $objectManager->getMetadataFactory()->getMetadataFor($this->entityName);

        $this->entityRepository = $objectManager->getRepository($this->entityName);
        $this->idFieldName      = $metadata->getSingleIdentifierFieldName();
        $this->objectManager    = $objectManager;
    }

    /**
     * {@inheritdoc}
     */
    public function search($query, $page, $perPage, $searchById = false)
    {
        $this->checkAllDependenciesInjected();

        $page = (int)$page > 0 ? (int)$page : 1;
        $perPage = (int)$perPage > 0 ? (int)$perPage : 10;
        $firstResult = ($page - 1) * $perPage;
        $perPage++;

        if ($searchById) {
            $items = $this->findById($query);
        } else {
            $items = $this->searchEntities($query, $firstResult, $perPage);
        }

        $hasMore = count($items) === $perPage;
        if ($hasMore) {
            $items = array_slice($items, 0, $perPage - 1);
        }

        return [
            'results' => $this->convertItems($items),
            'more'    => $hasMore
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function convertItem($item)
    {
        $result = [];

        if ($this->idFieldName) {
            $result[$this->idFieldName] = $this->getPropertyValue($this->idFieldName, $item);
        }

        foreach ($this->getProperties() as $property) {
            $result[$property] = (string)$this->getPropertyValue($property, $item);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityName()
    {
        return $this->entityName;
    }

    public function setAclHelper(AclHelper $aclHelper)
    {
        $this->aclHelper = $aclHelper;
    }

    public function setPropertyAccessor(PropertyAccessorInterface $propertyAccessor)
    {
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * @throws \RuntimeException
     */
    protected function checkAllDependenciesInjected()
    {
        if (!$this->indexer || !$this->entitySearchAlias || !$this->entityRepository || !$this->idFieldName) {
            throw new \RuntimeException('Search handler is not fully configured');
        }
    }

    /**
     * Search and return entities
     *
     * @param string $search
     * @param int    $firstResult
     * @param int    $maxResults
     * @return array
     */
    protected function searchEntities($search, $firstResult, $maxResults)
    {
        $entityIds = $this->searchIds($search, $firstResult, $maxResults);

        $resultEntities = [];
        if ($entityIds) {
            $unsortedEntities = $this->getEntitiesByIds($entityIds);

            /**
             * We need to sort entities in the same order given by method searchIds.
             *
             * Should be not necessary after implementation of BAP-5691.
             */
            $entityByIdHash = [];

            foreach ($unsortedEntities as $entity) {
                $entityByIdHash[$this->getPropertyValue($this->idFieldName, $entity)] = $entity;
            }

            foreach ($entityIds as $entityId) {
                if (isset($entityByIdHash[$entityId])) {
                    $resultEntities[] = $entityByIdHash[$entityId];
                }
            }
        }

        return $resultEntities;
    }

    /**
     * @param array $entityIds
     * @return array
     */
    protected function getEntitiesByIds(array $entityIds)
    {
        $entityIds = array_filter(
            $entityIds,
            function ($id) {
                return $id !== null && $id !== '';
            }
        );
        if ($entityIds) {
            /** @var QueryBuilder $queryBuilder */
            $queryBuilder = $this->entityRepository->createQueryBuilder('e');
            $queryBuilder->where($queryBuilder->expr()->in('e.' . $this->idFieldName, ':entityIds'));
            $queryBuilder->setParameter('entityIds', $entityIds);

            try {
                $query = $queryBuilder->getQuery();
                return null !== $this->aclHelper
                    ? $this->aclHelper->apply($query)->getResult()
                    : $query->getResult();
            } catch (\Exception $exception) {
                if ($this->logger) {
                    $this->logger->critical($exception->getMessage());
                }
            }
        }

        return [];
    }

    /**
     * @param string $search
     * @param int    $firstResult
     * @param int    $maxResults
     * @return array
     */
    protected function searchIds($search, $firstResult, $maxResults)
    {
        $result   = $this->indexer->simpleSearch($search, $firstResult, $maxResults, $this->entitySearchAlias);
        $elements = $result->getElements();

        $ids = [];
        foreach ($elements as $element) {
            $ids[] = $element->getRecordId();
        }

        return $ids;
    }

    /**
     * Get search results data by id
     *
     * @param int $query
     *
     * @return array
     */
    protected function findById($query)
    {
        $ids = [];
        if ($query) {
            $ids = explode(',', $query);
        }

        return $this->getEntitiesByIds($ids);
    }

    /**
     * @param array $items
     * @return array
     */
    protected function convertItems(array $items)
    {
        $result = [];
        foreach ($items as $item) {
            $result[] = $this->convertItem($item);
        }
        return $result;
    }

    /**
     * @param string $propertyPath
     * @param object|array $item
     * @return mixed
     */
    protected function getPropertyValue($propertyPath, $item)
    {
        if (!(is_object($item) || is_array($item))) {
            return null;
        }

        if (is_array($item)) {
            $keys = array_map(
                function ($key) {
                    return sprintf('[%s]', $key);
                },
                explode('.', $propertyPath)
            );
            $propertyPath = implode('', $keys);
        }

        if (!$this->propertyAccessor->isReadable($item, $propertyPath)) {
            return null;
        }

        return $this->propertyAccessor->getValue($item, $propertyPath);
    }
}
