<?php

namespace Oro\Bundle\ScopeBundle\Manager;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIteratorInterface;
use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\ScopeBundle\Entity\Repository\ScopeRepository;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
use Oro\Component\DependencyInjection\ServiceLink;
use Oro\Component\PropertyAccess\PropertyAccessor;

/**
 * Creates / finds scopes and scope criteria.
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ScopeManager
{
    const BASE_SCOPE = 'base_scope';

    /**
     * @var ScopeEntityStorage
     */
    protected $entityStorage;

    /**
     * @var EntityFieldProvider
     */
    protected $entityFieldProvider;

    /**
     * @var ServiceLink
     */
    protected $entityFieldProviderLink;

    /**
     * @var PropertyAccessor
     */
    protected $propertyAccessor;

    /**
     * @var array
     */
    protected $fields;

    /**
     * @param ScopeEntityStorage $entityStorage
     * @param ServiceLink $entityFieldProviderLink
     */
    public function __construct(ScopeEntityStorage $entityStorage, ServiceLink $entityFieldProviderLink)
    {
        $this->entityStorage = $entityStorage;
        $this->entityFieldProviderLink = $entityFieldProviderLink;
    }

    /**
     * @var ScopeCriteriaProviderInterface[]
     */
    protected $providers = [];

    /**
     * @var array|null
     */
    protected $nullContext = null;

    /**
     * @param string            $scopeType
     * @param array|object|null $context
     * @return Scope|null
     */
    public function find($scopeType, $context = null)
    {
        $criteria = $this->getCriteria($scopeType, $context);

        $scope = $this->getScopeRepository()->findOneByCriteria($criteria);
        if (!$scope) {
            $scope = $this->entityStorage->getScheduledForInsertByCriteria($criteria);
        }

        return $scope;
    }

    /**
     * @param string            $scopeType
     * @param array|object|null $context
     * @return Scope
     */
    public function findMostSuitable($scopeType, $context = null)
    {
        $criteria = $this->getCriteria($scopeType, $context);

        return $this->getScopeRepository()->findMostSuitable($criteria);
    }

    /**
     * @param string            $scopeType
     * @param array|object|null $context
     * @return BufferedQueryResultIterator|\Oro\Bundle\ScopeBundle\Entity\Scope[]
     */
    public function findBy($scopeType, $context = null)
    {
        $criteria = $this->getCriteria($scopeType, $context);

        return $this->getScopeRepository()->findByCriteria($criteria);
    }

    /**
     * @return Scope
     */
    public function findDefaultScope()
    {
        return $this->find(self::BASE_SCOPE, $this->getNullContext());
    }

    /**
     * @param string            $scopeType
     * @param array|object|null $context
     * @return BufferedQueryResultIteratorInterface|Scope[]
     */
    public function findRelatedScopes($scopeType, $context = null)
    {
        $criteria = $this->getCriteriaForRelatedScopes($scopeType, $context);

        return $this->getScopeRepository()->findByCriteria($criteria);
    }

    /**
     * @param string            $scopeType
     * @param array|object|null $context
     * @return array
     */
    public function findRelatedScopeIds($scopeType, $context = null)
    {
        $criteria = $this->getCriteriaForRelatedScopes($scopeType, $context);

        return $this->getScopeRepository()->findIdentifiersByCriteria($criteria);
    }

    /**
     * @param string            $scopeType
     * @param array|object|null $context
     *
     * @return array
     */
    public function findRelatedScopeIdsWithPriority($scopeType, $context = null)
    {
        $criteria = $this->getCriteria($scopeType, $context);

        return $this->getScopeRepository()->findIdentifiersByCriteriaWithPriority($criteria);
    }

    /**
     * @param string            $scopeType
     * @param array|object|null $context
     * @param bool              $flush
     * @return Scope
     */
    public function findOrCreate($scopeType, $context = null, $flush = true)
    {
        $scope = $this->find($scopeType, $context);
        if (!$scope) {
            $criteria = $this->getCriteria($scopeType, $context);
            $scope = $this->createScopeByCriteria($criteria, $flush);
        }

        return $scope;
    }

    /**
     * @param ScopeCriteria $criteria
     * @param bool          $flush
     * @return Scope
     */
    public function createScopeByCriteria(ScopeCriteria $criteria, $flush = true)
    {
        if ($scheduledScope = $this->entityStorage->getScheduledForInsertByCriteria($criteria)) {
            return $scheduledScope;
        }

        $scope = new Scope();
        $propertyAccessor = $this->getPropertyAccessor();
        foreach ($criteria as $fieldName => $value) {
            if ($value !== null) {
                $propertyAccessor->setValue($scope, $fieldName, $value);
            }
        }

        $this->entityStorage->scheduleForInsert($scope, $criteria);
        if ($flush) {
            $this->entityStorage->flush();
        }

        return $scope;
    }

    /**
     * @param string $scopeType
     * @param        $provider
     */
    public function addProvider($scopeType, $provider)
    {
        $this->providers[$scopeType][] = $provider;
    }

    /**
     * @param string $scopeType
     * @return array
     */
    public function getScopeEntities($scopeType)
    {
        $entities = [];

        $providers = $this->getProviders($scopeType);
        foreach ($providers as $provider) {
            $entities[$provider->getCriteriaField()] = $provider->getCriteriaValueType();
        }

        return $entities;
    }

    /**
     * @param Scope  $scope
     * @param string $type
     * @return ScopeCriteria
     */
    public function getCriteriaByScope(Scope $scope, $type)
    {
        $criteria = $this->getNullContext();
        foreach ($this->getProviders($type) as $provider) {
            $field = $provider->getCriteriaField();
            $criteria[$field] = $this->getPropertyAccessor()->getValue($scope, $field);
        }

        return new ScopeCriteria($criteria, $this->getFields());
    }

    /**
     * @param string            $scopeType
     * @param array|object|null $context
     * @return ScopeCriteria
     */
    public function getCriteria($scopeType, $context = null)
    {
        $criteria = [];
        if (self::BASE_SCOPE === $scopeType && is_array($context)) {
            $criteria = $context;
        } else {
            $providers = $this->getProviders($scopeType);
            foreach ($providers as $provider) {
                if (null === $context) {
                    $criteria = array_merge($criteria, $provider->getCriteriaForCurrentScope());
                } else {
                    $criteria = array_merge($criteria, $provider->getCriteriaByContext($context));
                }
            }
        }
        foreach ($this->getNullContext() as $emptyKey => $emptyValue) {
            if (!isset($criteria[$emptyKey])) {
                $criteria[$emptyKey] = $emptyValue;
            }
        }

        return new ScopeCriteria($criteria, $this->getFields());
    }


    /**
     * @param Scope $scope
     * @param ScopeCriteria $criteria
     * @param string $scopeType
     *
     * @return bool
     */
    public function isScopeMatchCriteria(Scope $scope, ScopeCriteria $criteria, $scopeType)
    {
        $criteriaContext = $criteria->toArray();
        $scopeCriteriaContext = $this->getCriteriaByScope($scope, $scopeType)
            ->toArray();

        foreach ($scopeCriteriaContext as $field => $value) {
            if (null !== $value && $criteriaContext[$field] !== $value) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array
     */
    protected function getNullContext()
    {
        if ($this->nullContext === null) {
            $this->nullContext = [];
            foreach ($this->getFields() as $field) {
                $this->nullContext[$field['name']] = null;
            }
        }

        return $this->nullContext;
    }

    /**
     * @return PropertyAccessor
     */
    protected function getPropertyAccessor()
    {
        if (null === $this->propertyAccessor) {
            $this->propertyAccessor = new PropertyAccessor();
        }

        return $this->propertyAccessor;
    }

    /**
     * @param string $scopeType
     * @return ScopeCriteriaProviderInterface[]
     */
    protected function getProviders($scopeType)
    {
        $rawProviders = empty($this->providers[$scopeType]) ? [] : $this->providers[$scopeType];
        $providers = [];
        foreach ($rawProviders as $provider) {
            if ($provider instanceof ServiceLink) {
                $provider = $provider->getService();
            }
            $providers[] = $provider;
        }

        return $providers;
    }

    /**
     * @return ScopeRepository
     */
    protected function getScopeRepository()
    {
        return $this->entityStorage->getRepository();
    }

    /**
     * @param string            $scopeType
     * @param array|object|null $context
     * @return ScopeCriteria
     */
    public function getCriteriaForRelatedScopes($scopeType, $context)
    {
        $criteria = $this->getNullContext();
        /** @var ScopeCriteriaProviderInterface[] $providers */
        $providers = $this->getProviders($scopeType);
        foreach ($providers as $provider) {
            $localCriteria = $provider->getCriteriaByContext($context);
            if (count($localCriteria) === 0) {
                $localCriteria = [$provider->getCriteriaField() => ScopeCriteria::IS_NOT_NULL];
            }
            $criteria = array_merge($criteria, $localCriteria);
        }

        return new ScopeCriteria($criteria, $this->getFields());
    }

    /**
     * @return EntityFieldProvider
     */
    protected function getEntityFieldProvider()
    {
        if (!$this->entityFieldProvider) {
            $this->entityFieldProvider = $this->entityFieldProviderLink->getService();
        }

        return $this->entityFieldProvider;
    }

    /**
     * @return array
     */
    protected function getFields()
    {
        if ($this->fields === null) {
            $this->fields = $this->getEntityFieldProvider()->getRelations(Scope::class, false, true, false);
        }

        return $this->fields;
    }
}
