<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

interface ChannelInterface
{
    /**
     * Returns label for UI
     *
     * @return string
     */
    public function getLabel();
}
