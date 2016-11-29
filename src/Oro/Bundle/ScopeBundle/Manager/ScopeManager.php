<?php

namespace Oro\Bundle\ScopeBundle\Manager;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\ScopeBundle\Entity\Repository\ScopeRepository;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
use Oro\Component\DependencyInjection\ServiceLink;
use Oro\Component\PropertyAccess\PropertyAccessor;

class ScopeManager
{
    const BASE_SCOPE = 'base_scope';

    /**
     * @var ManagerRegistry
     */
    protected $registry;

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
     * @param ManagerRegistry $registry
     * @param ServiceLink $entityFieldProviderLink
     */
    public function __construct(ManagerRegistry $registry, ServiceLink $entityFieldProviderLink)
    {
        $this->registry = $registry;
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
     * @param string $scopeType
     * @param array|object|null $context
     * @return Scope
     */
    public function find($scopeType, $context = null)
    {
        $criteria = $this->getCriteria($scopeType, $context);
        return $this->getScopeRepository()->findOneByCriteria($criteria);
    }

    /**
     * @param $scopeType
     * @param null $context
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
        $criteria = new ScopeCriteria($this->getNullContext());
        return $this->getScopeRepository()->findOneByCriteria($criteria);
    }

    /**
     * @param $scopeType
     * @param array|object|null $context
     * @return BufferedQueryResultIterator|Scope[]
     */
    public function findRelatedScopes($scopeType, $context = null)
    {
        $criteria = $this->getCriteriaForRelatedScopes($scopeType, $context);

        /** @var ScopeRepository $scopeRepository */
        $scopeRepository = $this->registry->getManagerForClass(Scope::class)
            ->getRepository(Scope::class);

        return $scopeRepository->findByCriteria($criteria);
    }

    /**
     * @param $scopeType
     * @param null $context
     * @return array
     */
    public function findRelatedScopeIds($scopeType, $context = null)
    {
        $criteria = $this->getCriteriaForRelatedScopes($scopeType, $context);

        /** @var ScopeRepository $scopeRepository */
        $scopeRepository = $this->registry->getManagerForClass(Scope::class)
            ->getRepository(Scope::class);

        return $scopeRepository->findIdentifiersByCriteria($criteria);
    }

    /**
     * @param string     $scopeType
     * @param array|null $context
     *
     * @return array
     */
    public function findRelatedScopeIdsWithPriority($scopeType, $context = null)
    {
        $criteria = $this->getCriteria($scopeType, $context);

        /** @var ScopeRepository $scopeRepository */
        $scopeRepository = $this->registry->getManagerForClass(Scope::class)
            ->getRepository(Scope::class);

        return $scopeRepository->findIdentifiersByCriteriaWithPriority($criteria);
    }

    /**
     * @param string $scopeType
     * @param array|object $context
     * @return Scope
     */
    public function findOrCreate($scopeType, $context = null)
    {
        $criteria = $this->getCriteria($scopeType, $context);

        /** @var ScopeRepository $repository */
        $repository = $this->registry->getManagerForClass(Scope::class)
            ->getRepository(Scope::class);
        $scope = $repository->findOneByCriteria($criteria);
        if (!$scope) {
            $scope = new Scope();
            $propertyAccessor = $this->getPropertyAccessor();
            foreach ($criteria as $fieldName => $value) {
                if ($value !== null) {
                    $propertyAccessor->setValue($scope, $fieldName, $value);
                }
            }

            /** @var EntityManager $manager */
            $manager = $this->registry->getManagerForClass(Scope::class);
            $manager->persist($scope);
            $manager->flush($scope);
        }

        return $scope;
    }

    /**
     * @param string $scopeType
     * @param $provider
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
     * @param Scope $scope
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

        return new ScopeCriteria($criteria);
    }

    /**
     * @param string $scopeType
     * @param array $context
     * @return ScopeCriteria
     */
    public function getCriteria($scopeType, $context = null)
    {
        $criteria = [];
        if (self::BASE_SCOPE == $scopeType && is_array($context)) {
            $criteria = $context;
        } else {
            /** @var ScopeCriteriaProviderInterface[] $providers */
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

        return new ScopeCriteria($criteria);
    }

    /**
     * @return array
     */
    protected function getNullContext()
    {
        if ($this->nullContext === null) {
            $this->nullContext = [];
            $fields = $this->getEntityFieldProvider()->getRelations(Scope::class);
            foreach ($fields as $field) {
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
     * @param $scopeType
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
        $repository = $this->registry->getManagerForClass(Scope::class)->getRepository(Scope::class);

        return $repository;
    }

    /**
     * @param $scopeType
     * @param $context
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

        return new ScopeCriteria($criteria);
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
}
