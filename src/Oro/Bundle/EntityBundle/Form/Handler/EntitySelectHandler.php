<?php

namespace Oro\Bundle\EntityBundle\Form\Handler;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\FormBundle\Autocomplete\SearchHandler;

class EntitySelectHandler extends SearchHandler
{
    /** @var array */
    protected $defaultPropertySet = ['text'];

    /** @var string */
    protected $currentField;

    /** @var ManagerRegistry */
    protected $registry;

    public function __construct()
    {
        // give some values in order to prevent warnings
        parent::__construct(false, []);
    }

    /**
     * {@inheritdoc}
     */
    public function initDoctrinePropertiesByManagerRegistry(ManagerRegistry $managerRegistry)
    {
        $this->registry = $managerRegistry;
    }

    /**
     * @param string $entityName  Entity name to prepare search handler for
     * @param string $targetField Entity field to search by and include to search results
     */
    public function initForEntity($entityName, $targetField)
    {
        $this->entityName = str_replace('_', '\\', $entityName);
        $this->initDoctrinePropertiesByEntityManager($this->registry->getManagerForClass($this->entityName));

        $this->properties   = array_unique(array_merge($this->defaultPropertySet, [$targetField]));
        $this->currentField = $targetField;
    }

    /**
     * {@inheritdoc}
     */
    public function search($query, $page, $perPage, $searchById = false)
    {
        list($query, $targetEntity, $targetField) = explode(',', $query);
        $this->initForEntity($targetEntity, $targetField);

        return parent::search($query, $page, $perPage, $searchById);
    }

    /**
     * {@inheritdoc}
     */
    public function convertItem($item)
    {
        $result = parent::convertItem($item);

        if ($this->idFieldName !== 'id' && !empty($result[$this->idFieldName])) {
            $result = array_merge($result, ['id' => $result[$this->idFieldName]]);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function searchEntities($search, $firstResult, $maxResults)
    {
        $queryBuilder = $this->entityRepository->createQueryBuilder('e');

        $queryBuilder->where(
            $queryBuilder->expr()->like(
                'e.' . $this->currentField,
                $queryBuilder->expr()->literal($search . '%')
            )
        );
        $queryBuilder->setMaxResults($maxResults);
        $queryBuilder->setFirstResult($firstResult);

        $query = $this->aclHelper->apply($queryBuilder, 'VIEW');

        return $query->getArrayResult();
    }

    /**
     * @throws \RuntimeException
     */
    protected function checkAllDependenciesInjected()
    {
        if (!$this->properties || !$this->currentField || !$this->entityRepository || !$this->idFieldName) {
            throw new \RuntimeException('Search handler is not fully configured');
        }
    }
}
