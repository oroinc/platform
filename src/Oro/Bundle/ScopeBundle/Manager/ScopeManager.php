<?php

namespace Oro\Bundle\ScopeBundle\Manager;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIteratorInterface;
use Oro\Bundle\ScopeBundle\Entity\Repository\ScopeRepository;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Exception\NotSupportedCriteriaValueException;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
use Psr\Container\ContainerInterface;
use Symfony\Component\PropertyAccess\Exception\AccessException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Creates / finds scopes and scope criteria.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ScopeManager
{
    public const BASE_SCOPE = 'base_scope';

    /** @var ContainerInterface */
    private $providerContainer;

    /** @var array [scope type => [ScopeCriteriaProviderInterface, ...], ...] */
    private $providers;

    /** @var ScopeEntityStorage */
    private $entityStorage;

    /** @var PropertyAccessorInterface */
    private $propertyAccessor;

    /** @var array|null */
    private $nullContext;

    /**
     * @param ContainerInterface        $providerContainer
     * @param array                     $providers
     * @param ScopeEntityStorage        $entityStorage
     * @param PropertyAccessorInterface $propertyAccessor
     */
    public function __construct(
        ContainerInterface $providerContainer,
        array $providers,
        ScopeEntityStorage $entityStorage,
        PropertyAccessorInterface $propertyAccessor
    ) {
        $this->providerContainer = $providerContainer;
        $this->providers = $providers;
        $this->entityStorage = $entityStorage;
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * @param string            $scopeType
     * @param array|object|null $context
     *
     * @return Scope|null
     */
    public function find(string $scopeType, $context = null): ?Scope
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
     *
     * @return Scope|null
     */
    public function findMostSuitable(string $scopeType, $context = null): ?Scope
    {
        $criteria = $this->getCriteria($scopeType, $context);

        return $this->getScopeRepository()->findMostSuitable($criteria);
    }

    /**
     * @param string            $scopeType
     * @param array|object|null $context
     *
     * @return BufferedQueryResultIterator|Scope[]
     */
    public function findBy(string $scopeType, $context = null)
    {
        $criteria = $this->getCriteria($scopeType, $context);

        return $this->getScopeRepository()->findByCriteria($criteria);
    }

    /**
     * @return Scope|null
     */
    public function findDefaultScope(): ?Scope
    {
        return $this->find(self::BASE_SCOPE, $this->getNullContext());
    }

    /**
     * @param string            $scopeType
     * @param array|object|null $context
     *
     * @return BufferedQueryResultIteratorInterface|Scope[]
     */
    public function findRelatedScopes(string $scopeType, $context = null)
    {
        $criteria = $this->getCriteriaForRelatedScopes($scopeType, $context);

        return $this->getScopeRepository()->findByCriteria($criteria);
    }

    /**
     * @param string            $scopeType
     * @param array|object|null $context
     *
     * @return BufferedQueryResultIteratorInterface|Scope[]
     */
    public function findRelatedScopeIds(string $scopeType, $context = null)
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
    public function findRelatedScopeIdsWithPriority(string $scopeType, $context = null): array
    {
        $criteria = $this->getCriteria($scopeType, $context);

        return $this->getScopeRepository()->findIdentifiersByCriteriaWithPriority($criteria);
    }

    /**
     * @param string            $scopeType
     * @param array|object|null $context
     * @param bool              $flush
     *
     * @return Scope
     */
    public function findOrCreate(string $scopeType, $context = null, bool $flush = true): Scope
    {
        $criteria = $this->getCriteria($scopeType, $context);
        $scope = $this->getScopeRepository()->findOneByCriteria($criteria);
        if (!$scope) {
            $scope = $this->entityStorage->getScheduledForInsertByCriteria($criteria);
        }
        if (!$scope) {
            $scope = $this->createScopeByCriteria($criteria, $flush);
        }

        return $scope;
    }

    /**
     * @param ScopeCriteria $criteria
     * @param bool          $flush
     *
     * @return Scope
     */
    public function createScopeByCriteria(ScopeCriteria $criteria, $flush = true): Scope
    {
        $scheduledScope = $this->entityStorage->getScheduledForInsertByCriteria($criteria);
        if ($scheduledScope) {
            return $scheduledScope;
        }

        $scope = new Scope();
        foreach ($criteria as $fieldName => $value) {
            if ($value !== null) {
                $this->propertyAccessor->setValue($scope, $fieldName, $value);
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
     *
     * @return array [criteria filed => criteria value type, ...]
     */
    public function getScopeEntities(string $scopeType): array
    {
        $entities = [];

        $providerIds = $this->getProviderIds($scopeType);
        foreach ($providerIds as $providerId) {
            $provider = $this->getProvider($providerId);
            $entities[$provider->getCriteriaField()] = $provider->getCriteriaValueType();
        }

        return $entities;
    }

    /**
     * @param Scope             $scope
     * @param string            $scopeType
     * @param array|object|null $context
     *
     * @return ScopeCriteria
     */
    public function getCriteriaByScope(Scope $scope, string $scopeType, $context = null): ScopeCriteria
    {
        $criteria = $this->getNullContext();
        $providerIds = $this->getProviderIds($scopeType);
        foreach ($providerIds as $providerId) {
            $provider = $this->getProvider($providerId);
            $field = $provider->getCriteriaField();
            $value = $this->getCriteriaValueFromContext($context, $provider);
            if (false === $value) {
                $value = $this->propertyAccessor->getValue($scope, $field);
            }
            $criteria[$field] = $value;
        }

        return new ScopeCriteria($criteria, $this->getScopeClassMetadata());
    }

    /**
     * @param string            $scopeType
     * @param array|object|null $context
     *
     * @return ScopeCriteria
     */
    public function getCriteria(string $scopeType, $context = null): ScopeCriteria
    {
        $criteria = [];
        if (self::BASE_SCOPE === $scopeType && is_array($context)) {
            $criteria = $context;
        } else {
            $providerIds = $this->getProviderIds($scopeType);
            foreach ($providerIds as $providerId) {
                $provider = $this->getProvider($providerId);
                if (null === $context) {
                    $criteria[$provider->getCriteriaField()] = $provider->getCriteriaValue();
                } else {
                    $value = $this->getCriteriaValueFromContext($context, $provider);
                    if (false !== $value) {
                        $criteria[$provider->getCriteriaField()] = $value;
                    }
                }
            }
        }
        foreach ($this->getNullContext() as $emptyKey => $emptyValue) {
            if (!isset($criteria[$emptyKey])) {
                $criteria[$emptyKey] = $emptyValue;
            }
        }

        return new ScopeCriteria($criteria, $this->getScopeClassMetadata());
    }


    /**
     * @param Scope         $scope
     * @param ScopeCriteria $criteria
     * @param string        $scopeType
     *
     * @return bool
     */
    public function isScopeMatchCriteria(Scope $scope, ScopeCriteria $criteria, string $scopeType): bool
    {
        $criteriaContext = $criteria->toArray();
        $scopeCriteriaContext = $this->getCriteriaByScope($scope, $scopeType)->toArray();

        foreach ($scopeCriteriaContext as $field => $value) {
            if (null !== $value && $criteriaContext[$field] !== $value) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string            $scopeType
     * @param array|object|null $context
     *
     * @return ScopeCriteria
     */
    public function getCriteriaForRelatedScopes(string $scopeType, $context = null): ScopeCriteria
    {
        $criteria = $this->getNullContext();
        $providerIds = $this->getProviderIds($scopeType);
        foreach ($providerIds as $providerId) {
            $provider = $this->getProvider($providerId);
            $value = $this->getCriteriaValueFromContext($context, $provider);
            if (false === $value) {
                $value = ScopeCriteria::IS_NOT_NULL;
            }
            $criteria[$provider->getCriteriaField()] = $value;
        }

        return new ScopeCriteria($criteria, $this->getScopeClassMetadata());
    }

    /**
     * @param array|object|null              $context
     * @param ScopeCriteriaProviderInterface $provider
     *
     * @return mixed
     */
    private function getCriteriaValueFromContext($context, ScopeCriteriaProviderInterface $provider)
    {
        if (!is_object($context) && !is_array($context)) {
            return false;
        }

        $propertyPath = $provider->getCriteriaField();
        if (is_array($context)) {
            $propertyPath = '[' . $propertyPath . ']';
        }
        try {
            $value = $this->propertyAccessor->getValue($context, $propertyPath);
            if (null === $value) {
                return false;
            }
        } catch (AccessException $e) {
            return false;
        }

        if (!is_a($value, $provider->getCriteriaValueType())
            && !is_array($value)
            && ScopeCriteria::IS_NOT_NULL !== $value
        ) {
            throw new NotSupportedCriteriaValueException(sprintf(
                'The type %s is not supported for context[%s]. Expected %s, null, array or "%s".',
                is_object($value) ? get_class($value) : gettype($value),
                $provider->getCriteriaField(),
                $provider->getCriteriaValueType(),
                ScopeCriteria::IS_NOT_NULL
            ));
        }

        return $value;
    }

    /**
     * @return array
     */
    private function getNullContext(): array
    {
        if (null === $this->nullContext) {
            $this->nullContext = array_fill_keys(
                $this->getScopeClassMetadata()->getAssociationNames(),
                null
            );
        }

        return $this->nullContext;
    }

    /**
     * Gets IDs of providers for the given scope type.
     * The providers are sorted by the priority, the higher the priority,
     * the closer the provider to the top of the list.
     *
     * @param string $scopeType
     *
     * @return string[]
     */
    private function getProviderIds(string $scopeType): array
    {
        return $this->providers[$scopeType] ?? [];
    }

    /**
     * @param string $providerId
     *
     * @return ScopeCriteriaProviderInterface
     */
    private function getProvider(string $providerId): ScopeCriteriaProviderInterface
    {
        return $this->providerContainer->get($providerId);
    }

    /**
     * @return ScopeRepository
     */
    private function getScopeRepository(): ScopeRepository
    {
        return $this->entityStorage->getRepository();
    }

    /**
     * @return ClassMetadata
     */
    private function getScopeClassMetadata(): ClassMetadata
    {
        return $this->entityStorage->getClassMetadata();
    }
}
