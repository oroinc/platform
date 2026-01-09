<?php

namespace Oro\Bundle\ConfigBundle\Provider\Value\Entity;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\ConfigBundle\Provider\Value\ValueProviderInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

/**
 * Provides configuration values by retrieving entity IDs based on specified criteria.
 *
 * Implements {@see ValueProviderInterface} to supply configuration values that are derived from
 * entity lookups. This provider queries the database for an entity matching the specified
 * criteria and returns its ID. This is useful for configuration fields that need to store
 * references to specific entities (e.g., a default organization or business unit) and
 * retrieve their IDs dynamically based on predefined search criteria.
 */
class EntityIdByCriteriaProvider implements ValueProviderInterface
{
    /**
     * @var EntityRepository|DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @var string
     */
    private $entityClass;

    /**
     * @var array
     */
    private $defaultEntityCriteria;

    public function __construct(DoctrineHelper $doctrineHelper, string $entityClass, array $defaultEntityCriteria)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->entityClass = $entityClass;
        $this->defaultEntityCriteria = $defaultEntityCriteria;
    }

    #[\Override]
    public function getValue()
    {
        $entity = $this->doctrineHelper->getEntityRepositoryForClass($this->entityClass)
            ->findOneBy($this->defaultEntityCriteria);

        if (!$entity) {
            return null;
        }

        return $entity->getId();
    }
}
