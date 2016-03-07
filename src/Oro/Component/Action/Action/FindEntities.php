<?php

namespace Oro\Component\Action\Action;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;

use Symfony\Component\PropertyAccess\PropertyPathInterface;

use Oro\Component\Action\Exception\NotManageableEntityException;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\Action\Model\ContextAccessor;

class FindEntities extends AbstractAction
{
    /** @var array */
    protected $options;

    /** @var ManagerRegistry */
    protected $registry;

    /**
     * @param ContextAccessor $contextAccessor
     * @param ManagerRegistry $registry
     */
    public function __construct(ContextAccessor $contextAccessor, ManagerRegistry $registry)
    {
        parent::__construct($contextAccessor);

        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        $entities = $this->getEntitiesByConditions($context);

        $this->contextAccessor->setValue($context, $this->options['attribute'], $entities);
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (empty($options['class'])) {
            throw new InvalidParameterException('Class name parameter is required');
        }

        if (empty($options['attribute'])) {
            throw new InvalidParameterException('Attribute name parameter is required');
        }

        if (!$options['attribute'] instanceof PropertyPathInterface) {
            throw new InvalidParameterException('Attribute must be valid property definition.');
        }

        $this->options = $this->validateConditionOptions($options);

        return $this;
    }

    /**
     * @param array $options
     * @return array
     * @throws InvalidParameterException
     */
    protected function validateConditionOptions(array $options)
    {
        if (empty($options['where']) && empty($options['order_by'])) {
            throw new InvalidParameterException('One of parameters "where" or "order_by" must be defined');
        }

        if (!empty($options['where']) && !is_array($options['where'])) {
            throw new InvalidParameterException('Parameter "where" must be array');
        } elseif (empty($options['where'])) {
            $options['where'] = [];
        }

        if (!empty($options['order_by']) && !is_array($options['order_by'])) {
            throw new InvalidParameterException('Parameter "order_by" must be array');
        } elseif (empty($options['order_by'])) {
            $options['order_by'] = [];
        }

        return $options;
    }

    /**
     * Returns entities according to "where" and "order_by" parameters
     *
     * @param mixed $context
     * @return Collection
     */
    protected function getEntitiesByConditions($context)
    {
        $entityClassName = $this->getEntityClassName();
        $queryBuilder = $this->getEntityManager($entityClassName)
            ->getRepository($entityClassName)
            ->createQueryBuilder('e');

        $this->addWhere($queryBuilder, $this->getWhere($context));
        $this->addParameters($queryBuilder, $this->getParameters($context));

        $orderBy = $this->getOrderBy($context);

        // apply sorting
        foreach ($orderBy as $field => $direction) {
            $field = 'e.' . $field;
            $queryBuilder->orderBy($field, $direction);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param QueryBuilder $qb
     * @param array $dataWhere
     */
    protected function addWhere(QueryBuilder $qb, $dataWhere)
    {
        if (isset($dataWhere['and'])) {
            foreach ((array)$dataWhere['and'] as $where) {
                $qb->andWhere($where);
            }
        }

        if (isset($dataWhere['or'])) {
            foreach ((array)$dataWhere['or'] as $where) {
                $qb->orWhere($where);
            }
        }
    }

    /**
     * @param QueryBuilder $qb
     * @param array $dataParams
     */
    protected function addParameters(QueryBuilder $qb, $dataParams)
    {
        $qb->setParameters($dataParams);
    }

    /**
     * @param string $entityClassName
     * @return EntityManager
     * @throws NotManageableEntityException
     */
    protected function getEntityManager($entityClassName)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->registry->getManagerForClass($entityClassName);
        if (!$entityManager) {
            throw new NotManageableEntityException($entityClassName);
        }

        return $entityManager;
    }

    /**
     * @return string
     */
    protected function getEntityClassName()
    {
        return $this->options['class'];
    }

    /**
     * @param mixed $context
     * @return array
     */
    protected function getWhere($context)
    {
        return $this->parseArrayValues($context, $this->options['where']);
    }

    /**
     * @param mixed $context
     * @return array
     */
    protected function getParameters($context)
    {
        return array_key_exists('query_parameters', $this->options)
            ? $this->parseArrayValues($context, $this->options['query_parameters'])
            : [];
    }

    /**
     * @param mixed $context
     * @return array
     */
    protected function getOrderBy($context)
    {
        return $this->parseArrayValues($context, $this->options['order_by']);
    }

    /**
     * @param mixed $context
     * @param array $data
     * @return array
     */
    protected function parseArrayValues($context, array $data)
    {
        foreach ($data as $key => $value) {
            $data[$key] = $this->contextAccessor->getValue($context, $value);
        }

        return $data;
    }
}
