<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;

/**
 * An instance of this class can be added to the config extras of the Context
 * to request to add only identifier fields to a result.
 */
class FilterIdentifierFieldsConfigExtra implements ConfigExtraInterface
{
    const NAME = 'identifier_fields_only';

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
        // no any modifications of the ConfigContext is required
    }

    /**
     * {@inheritdoc}
     */
    public function isPropagable()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheKeyPart()
    {
        return self::NAME;
    }
}
