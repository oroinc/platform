<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;

/**
 * An instance of this class can be added to the config extras of the Context
 * to request result's fields filtering
 */
class FilterFieldsConfigExtra implements ConfigExtraInterface
{
    const NAME = 'filter_fields';

    /** @var array */
    protected $excludeFields;

    /**
     * @param array $excludeFields
     */
    public function __construct($excludeFields = [])
    {
        $this->excludeFields = $excludeFields;
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
        $context->set(self::NAME, $this->excludeFields);
    }
}
