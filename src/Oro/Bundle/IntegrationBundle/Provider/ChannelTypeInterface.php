<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

interface ChannelTypeInterface
{
    /**
     * Returns label for UI
     *
     * @return string
     */
    public function getLabel();
}
