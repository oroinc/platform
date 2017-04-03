<?php

namespace Oro\Bundle\IntegrationBundle\Authentication\Token;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationToken;

trait IntegrationTokenAwareTrait
{
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @param Integration $integration
     */
    private function setTemporaryIntegrationToken(Integration $integration)
    {
        $integrationToken = new OrganizationToken($integration->getOrganization());
        $integrationToken->setAttribute('owner_description', 'Integration: '. $integration->getName());
        $this->tokenStorage->setToken($integrationToken);
    }
}
