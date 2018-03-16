<?php

namespace Oro\Bundle\IntegrationBundle\Authentication\Token;

use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

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
