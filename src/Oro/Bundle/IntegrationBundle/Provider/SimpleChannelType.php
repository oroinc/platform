<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

class SimpleChannelType implements ChannelInterface
{
    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'oro.integration.channel_type.simple.label';
    }
}
