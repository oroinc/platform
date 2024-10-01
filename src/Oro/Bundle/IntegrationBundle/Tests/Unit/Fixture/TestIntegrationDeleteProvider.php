<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Fixture;

use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Manager\DeleteProviderInterface;

class TestIntegrationDeleteProvider implements DeleteProviderInterface
{
    #[\Override]
    public function supports($type)
    {
        return true;
    }

    #[\Override]
    public function deleteRelatedData(Integration $integration)
    {
    }
}
