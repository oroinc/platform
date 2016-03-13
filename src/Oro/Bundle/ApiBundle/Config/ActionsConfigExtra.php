<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;

class ActionsConfigExtra implements ConfigExtraInterface, ConfigExtraSectionInterface
{
    const NAME = 'actions';

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
    public function isInheritable()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigType()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheKeyPart()
    {
        return self::NAME;
    }
}
