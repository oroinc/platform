<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

class SimpleChannelType implements ChannelInterface, IconAwareIntegrationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'oro.integration.integration_type.simple.label';
    }

    /**
     * @return string
     */
    public function getIcon()
    {
        return 'simple.png';
    }
}
