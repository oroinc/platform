<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

class SimpleChannelType implements ChannelInterface
{
    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'oro.integration.integration_type.simple.label';
    }
}
