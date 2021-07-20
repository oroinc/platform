<?php

namespace Oro\Bundle\ScopeBundle\Manager;

use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIteratorInterface;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Exception\NotSupportedCriteriaValueException;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
use Psr\Container\ContainerInterface;
use Symfony\Component\PropertyAccess\Exception\AccessException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Creates / finds scopes and scope criteria.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ScopeManager implements ResetInterface
{
    public const BASE_SCOPE = 'base_scope';

    /** @var ContainerInterface */
    private $providerContainer;

    /** @var array [scope type => [scope criteria provider id, ...], ...] */
    private $providers;

    /** @var ManagerRegistry */
    private $doctrine;

    /** @var ScopeDataAccessor */
    private $dataAccessor;

    /** @var ScopeCollection */
    private $scheduledForInsertScopes;

    /** @var PropertyAccessorInterface */
    private $propertyAccessor;

    /** @var array|null */
    private $nullContext;

    /** @var array [scope type => [ScopeCriteriaProviderInterface, ...], ...] */
    private $loadedProviders = [];

    public function __construct(
        array $providers,
        ContainerInterface $providerContainer,
        ManagerRegistry $doctrine,
        ScopeDataAccessor $dataAccessor,
        ScopeCollection $scheduledForInsertScopes,
        PropertyAccessorInterface $propertyAccessor
    ) {
        $this->providers = $providers;
        $this->providerContainer = $providerContainer;
        $this->doctrine = $doctrine;
        $this->dataAccessor = $dataAccessor;
        $this->scheduledForInsertScopes = $scheduledForInsertScopes;
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

        $scope = $this->dataAccessor->findOneByCriteria($criteria);
        if (null === $scope) {
            $scope = $this->scheduledForInsertScopes->get($criteria);
        }

        return $scope;
    }

    /**
     * @param string            $scopeType
     * @param array|object|null $context
     *
     * @return int|null
     */
    public function findId(string $scopeType, $context = null): ?int
    {
        $criteria = $this->getCriteria($scopeType, $context);

        return $this->dataAccessor->findIdentifierByCriteria($criteria);
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

        return $this->dataAccessor->findMostSuitableByCriteria($criteria);
    }

    /**
     * @param string            $scopeType
     * @param array|object|null $context
     *
     * @return BufferedQueryResultIteratorInterface|Scope[]
     */
    public function findBy(string $scopeType, $context = null): iterable
    {
        $criteria = $this->getCriteria($scopeType, $context);

        return $this->dataAccessor->findByCriteria($criteria);
    }

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
    public function findRelatedScopes(string $scopeType, $context = null): iterable
    {
        $criteria = $this->getCriteriaForRelatedScopes($scopeType, $context);

        return $this->dataAccessor->findByCriteria($criteria);
    }

    /**
     * @param string            $scopeType
     * @param array|object|null $context
     *
     * @return int[]
     */
    public function findRelatedScopeIds(string $scopeType, $context = null): array
    {
        $criteria = $this->getCriteriaForRelatedScopes($scopeType, $context);

        return $this->dataAccessor->findIdentifiersByCriteria($criteria);
    }

    /**
     * @param string            $scopeType
     * @param array|object|null $context
     *
     * @return int[]
     */
    public function findRelatedScopeIdsWithPriority(string $scopeType, $context = null): array
    {
        $criteria = $this->getCriteria($scopeType, $context);

        return $this->dataAccessor->findIdentifiersByCriteriaWithPriority($criteria);
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
        $scope = $this->dataAccessor->findOneByCriteria($criteria);
        if (null === $scope) {
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
        $scheduledScope = $this->scheduledForInsertScopes->get($criteria);
        if (null !== $scheduledScope) {
            return $scheduledScope;
        }

        $scope = new Scope();
        foreach ($criteria as $fieldName => $value) {
            if ($value !== null) {
                $this->propertyAccessor->setValue($scope, $fieldName, $value);
            }
        }

        $this->scheduledForInsertScopes->add($scope, $criteria);
        if ($flush) {
            $this->doctrine->getManagerForClass(Scope::class)
                ->flush($this->scheduledForInsertScopes->getAll());
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

        $providers = $this->getProviders($scopeType);
        foreach ($providers as $provider) {
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
        $providers = $this->getProviders($scopeType);
        foreach ($providers as $provider) {
            $field = $provider->getCriteriaField();
            $value = $this->getCriteriaValueFromContext($context, $provider);
            if (false === $value) {
                $value = $this->propertyAccessor->getValue($scope, $field);
            }
            $criteria[$field] = $value;
        }

        return new ScopeCriteria($criteria, $this->getClassMetadataFactory());
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
            $providers = $this->getProviders($scopeType);
            foreach ($providers as $provider) {
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

        return new ScopeCriteria($criteria, $this->getClassMetadataFactory());
    }

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
        $providers = $this->getProviders($scopeType);
        foreach ($providers as $provider) {
            $value = $this->getCriteriaValueFromContext($context, $provider);
            if (false === $value) {
                $value = ScopeCriteria::IS_NOT_NULL;
            }
            $criteria[$provider->getCriteriaField()] = $value;
        }

        return new ScopeCriteria($criteria, $this->getClassMetadataFactory());
    }

    /**
     * {@inheritDoc}
     */
    public function reset()
    {
        $this->loadedProviders = [];
    }

    /**
     * @param array|object|null              $context
     * @param ScopeCriteriaProviderInterface $provider
     *
     * @return mixed
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
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

    private function getNullContext(): array
    {
        if (null === $this->nullContext) {
            $this->nullContext = array_fill_keys(
                $this->getClassMetadataFactory()->getMetadataFor(Scope::class)->getAssociationNames(),
                null
            );
        }

        return $this->nullContext;
    }

    /**
     * Gets providers for the given scope type.
     * The providers are sorted by the priority, the higher the priority,
     * the closer the provider to the top of the list.
     *
     * @param string $scopeType
     *
     * @return ScopeCriteriaProviderInterface[]
     */
    private function getProviders(string $scopeType): array
    {
        if (!isset($this->loadedProviders[$scopeType])) {
            $providers = [];
            if (isset($this->providers[$scopeType])) {
                foreach ($this->providers[$scopeType] as $providerId) {
                    $providers[] = $this->providerContainer->get($providerId);
                }
            }
            $this->loadedProviders[$scopeType] = $providers;
        }

        return $this->loadedProviders[$scopeType];
    }

    private function getClassMetadataFactory(): ClassMetadataFactory
    {
        return $this->doctrine->getManagerForClass(Scope::class)->getMetadataFactory();
    }
}
