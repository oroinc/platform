<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;

/**
 * An instance of this class can be added to the config extras of the Context
 * to request to add only specific fields to a result.
 */
class FilterFieldsConfigExtra implements ConfigExtraInterface
{
    const NAME = 'filter_fields';

    /** @var array */
    protected $fieldFilters;

    /**
     * @param array $fieldFilters The list of fields that should be returned for a specified type of an entity.
     *                            [entity type => [field name, ...], ...]
     */
    public function __construct(array $fieldFilters)
    {
        $this->fieldFilters = $fieldFilters;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function configureContext(ConfigContext $context)
    {
        $context->set(self::NAME, $this->fieldFilters);
    }

    /**
     * {@inheritdoc}
     */
    public function isPropagable()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheKeyPart()
    {
        $result = '';
        foreach ($this->fieldFilters as $entity => $fields) {
            $result .= $entity . '(' . implode(',', $fields) . ')';
        }

        return 'fields:' . $result;
    }
}
