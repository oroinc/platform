<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;

class VirtualFieldsConfigExtra implements ConfigExtraInterface
{
    const NAME = 'virtual_fields';

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
    public function getCacheKeyPart()
    {
        return self::NAME;
    }
}
