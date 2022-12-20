<?php

namespace Oro\Bundle\IntegrationBundle\Authentication\Token;

use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationAwareTokenInterface;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Updates security token with Integration Organization and `owner_description` attribute.
 */
trait IntegrationTokenAwareTrait
{
    private TokenStorageInterface $tokenStorage;

    /**
     * @param Integration $integration
     * @return void
     */
    private function setTemporaryIntegrationToken(Integration $integration): void
    {
        $token = $this->tokenStorage->getToken();
        if ($token === null) {
            $token = new OrganizationToken($integration->getOrganization());
        } elseif ($token instanceof OrganizationAwareTokenInterface) {
            $token->setOrganization($integration->getOrganization());
        }

        $token->setAttribute('owner_description', 'Integration: '. $integration->getName());

        $this->tokenStorage->setToken($token);
    }
}
