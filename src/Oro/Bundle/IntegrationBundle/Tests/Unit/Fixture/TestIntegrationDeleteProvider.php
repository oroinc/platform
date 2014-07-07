<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Fixture;

use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Manager\DeleteProviderInterface;

class TestIntegrationDeleteProvider implements DeleteProviderInterface
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
    public function deleteRelatedData(Integration $integration)
    {
    }
}
