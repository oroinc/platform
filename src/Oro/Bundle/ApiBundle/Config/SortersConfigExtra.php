<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * An instance of this class can be added to the config extras of the Context
 * to request an information about fields that can be used to sort a result.
 */
class SortersConfigExtra implements ConfigExtraSectionInterface
{
    const NAME = ConfigUtil::SORTERS;

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
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigType()
    {
        return ConfigUtil::SORTERS;
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheKeyPart()
    {
        return self::NAME;
    }
}
