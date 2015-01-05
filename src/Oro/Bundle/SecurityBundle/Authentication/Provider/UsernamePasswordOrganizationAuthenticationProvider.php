<?php

namespace Oro\Bundle\SecurityBundle\Authentication\Provider;

use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Provider\DaoAuthenticationProvider;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\SecurityBundle\Authentication\Guesser\UserOrganizationGuesser;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;

class UsernamePasswordOrganizationAuthenticationProvider extends DaoAuthenticationProvider
{
    /**
     * {@inheritdoc}
     */
    public function authenticate(TokenInterface $token)
    {
        $guesser = new UserOrganizationGuesser();
        /**  @var TokenInterface $token */
        $authenticatedToken = parent::authenticate($token);

        /** @var User $user */
        $user         = $authenticatedToken->getUser();
        $organization = $guesser->guess($user, $token);

        if (!$organization) {
            throw new BadCredentialsException("You don't have active organization assigned.");
        } elseif (!$user->getOrganizations(true)->contains($organization)) {
            throw new BadCredentialsException(
                sprintf("You don't have access to organization '%s'", $organization->getName())
            );
        }

        $authenticatedToken = new UsernamePasswordOrganizationToken(
            $authenticatedToken->getUser(),
            $authenticatedToken->getCredentials(),
            $authenticatedToken->getProviderKey(),
            $organization,
            $authenticatedToken->getRoles()
        );

        return $authenticatedToken;
    }
}
