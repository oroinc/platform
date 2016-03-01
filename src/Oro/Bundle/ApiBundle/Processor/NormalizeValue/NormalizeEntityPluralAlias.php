<?php

namespace Oro\Bundle\ApiBundle\Processor\NormalizeValue;

use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;

class NormalizeEntityPluralAlias extends AbstractProcessor
{
    const REQUIREMENT = '[a-zA-Z]\w+';

    /** @var EntityAliasResolver */
    protected $entityAliasResolver;

    /**
     * @param EntityAliasResolver $entityAliasResolver
     */
    public function __construct(EntityAliasResolver $entityAliasResolver)
    {
        $this->entityAliasResolver = $entityAliasResolver;
    }

    /**
     * {@inheritdoc}
     */
    protected function getDataTypeString()
    {
        return 'entity plural alias';
    }

    /**
     * {@inheritdoc}
     */
    protected function getDataTypePluralString()
    {
        return 'entity plural aliases';
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
    protected function normalizeValue($value)
    {
        return $this->entityAliasResolver->getClassByPluralAlias($value);
    }
}
