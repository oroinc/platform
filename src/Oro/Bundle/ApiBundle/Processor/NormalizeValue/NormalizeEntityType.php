<?php

namespace Oro\Bundle\ApiBundle\Processor\NormalizeValue;

use Oro\Bundle\ApiBundle\Provider\EntityAliasResolverRegistry;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;

/**
 * Converts entity class name to entity type
 * Provides a regular expression that can be used to validate entity class name.
 */
class NormalizeEntityType extends AbstractProcessor
{
    public const REQUIREMENT = '[a-zA-Z][\w\\\\]+';

    private EntityAliasResolverRegistry $entityAliasResolverRegistry;
    private ?EntityAliasResolver $entityAliasResolver = null;

    public function __construct(EntityAliasResolverRegistry $entityAliasResolverRegistry)
    {
        $this->entityAliasResolverRegistry = $entityAliasResolverRegistry;
    }

    #[\Override]
    protected function getDataTypeString(): string
    {
        return 'entity class';
    }

    #[\Override]
    protected function getDataTypePluralString(): string
    {
        return 'entity classes';
    }

    #[\Override]
    protected function getRequirement(): string
    {
        return self::REQUIREMENT;
    }

    #[\Override]
    protected function isValueNormalizationRequired(mixed $value): bool
    {
        return str_contains($value, '\\');
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
        return $this->entityAliasResolver->getPluralAlias($value);
    }
}
