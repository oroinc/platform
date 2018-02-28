<?php

namespace Oro\Bundle\ApiBundle\Processor\NormalizeValue;

use Oro\Bundle\ApiBundle\Provider\EntityAliasResolverRegistry;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;

/**
 * Converts entity type to entity class name.
 * Provides a regular expression that can be used to validate entity type.
 */
class NormalizeEntityClass extends AbstractProcessor
{
    const REQUIREMENT = '[a-zA-Z]\w+';

    /** @var EntityAliasResolverRegistry */
    private $entityAliasResolverRegistry;

    /** @var EntityAliasResolver|null */
    private $entityAliasResolver;

    /**
     * @param EntityAliasResolverRegistry $entityAliasResolverRegistry
     */
    public function __construct(EntityAliasResolverRegistry $entityAliasResolverRegistry)
    {
        $this->entityAliasResolverRegistry = $entityAliasResolverRegistry;
    }

    /**
     * {@inheritdoc}
     */
    protected function getDataTypeString()
    {
        return 'entity type';
    }

    /**
     * {@inheritdoc}
     */
    protected function getDataTypePluralString()
    {
        return 'entity types';
    }

    /**
     * {@inheritdoc}
     */
    protected function getRequirement()
    {
        return self::REQUIREMENT;
    }

    /**
     * {@inheritdoc}
     */
    protected function isValueNormalizationRequired($value)
    {
        return false === strpos($value, '\\');
    }

    /**
     * {@inheritdoc}
     */
    protected function processNormalization(NormalizeValueContext $context)
    {
        $this->entityAliasResolver = $this->entityAliasResolverRegistry
            ->getEntityAliasResolver($context->getRequestType());
        try {
            parent::processNormalization($context);
        } finally {
            $this->entityAliasResolver = null;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function normalizeValue($value)
    {
        return $this->entityAliasResolver->getClassByPluralAlias($value);
    }
}
