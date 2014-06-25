<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Fixture;

use Oro\Bundle\IntegrationBundle\Provider\ChannelInterface as IntegrationInterface;

class TestIntegrationType implements IntegrationInterface
{
    /**
     * Returns label for UI
     *
     * @return string
     */
    public function getLabel()
    {
        return 'testLabel';
    }
}
