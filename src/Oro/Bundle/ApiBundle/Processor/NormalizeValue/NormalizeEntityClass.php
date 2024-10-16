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
    public const REQUIREMENT = '[a-zA-Z]\w+';

    private EntityAliasResolverRegistry $entityAliasResolverRegistry;
    private ?EntityAliasResolver $entityAliasResolver = null;

    public function __construct(EntityAliasResolverRegistry $entityAliasResolverRegistry)
    {
        $this->entityAliasResolverRegistry = $entityAliasResolverRegistry;
    }

    #[\Override]
    protected function getDataTypeString(): string
    {
        return 'entity type';
    }

    #[\Override]
    protected function getDataTypePluralString(): string
    {
        return 'entity types';
    }

    #[\Override]
    protected function getRequirement(): string
    {
        return self::REQUIREMENT;
    }

    #[\Override]
    protected function isValueNormalizationRequired(mixed $value): bool
    {
        return !str_contains($value, '\\');
    }

    #[\Override]
    protected function processNormalization(NormalizeValueContext $context): void
    {
        $this->entityAliasResolver = $this->entityAliasResolverRegistry
            ->getEntityAliasResolver($context->getRequestType());
        try {
            parent::processNormalization($context);
        } finally {
            $this->entityAliasResolver = null;
        }
    }

    #[\Override]
    protected function normalizeValue(mixed $value): mixed
    {
        return $this->entityAliasResolver->getClassByPluralAlias($value);
    }
}
