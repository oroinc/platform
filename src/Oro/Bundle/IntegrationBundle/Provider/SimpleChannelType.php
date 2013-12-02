<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

class SimpleChannelType implements ChannelTypeInterface
{
    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'oro.integration.channel_type.simple.label';
    }
}
