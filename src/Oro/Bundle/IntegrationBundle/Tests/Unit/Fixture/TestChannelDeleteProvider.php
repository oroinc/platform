<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Fixture;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Manager\DeleteProviderInterface;

class TestChannelDeleteProvider implements DeleteProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function supports($type)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteRelatedData(Channel $channel)
    {
    }
}
