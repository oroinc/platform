<?php

namespace Oro\Bundle\SecurityBundle\Authentication\Provider;

use Oro\Bundle\SecurityBundle\Authentication\Guesser\UserOrganizationGuesser;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationTokenFactoryInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Provider\DaoAuthenticationProvider;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

class UsernamePasswordOrganizationAuthenticationProvider extends DaoAuthenticationProvider
{
    /**
     * @var UsernamePasswordOrganizationTokenFactoryInterface
     */
    protected $tokenFactory;

    /**
     * @param UsernamePasswordOrganizationTokenFactoryInterface $tokenFactory
     */
    public function setTokenFactory(UsernamePasswordOrganizationTokenFactoryInterface $tokenFactory)
    {
        $this->tokenFactory = $tokenFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate(TokenInterface $token)
    {
        if (null === $this->tokenFactory) {
            throw new AuthenticationException(
                'Token Factory is not set in UsernamePasswordOrganizationAuthenticationProvider.'
            );
        }

        $guesser = new UserOrganizationGuesser();
        /**  @var TokenInterface $token */
        $authenticatedToken = parent::authenticate($token);

        /** @var User $user */
        $user         = $authenticatedToken->getUser();
        $organization = $guesser->guess($user, $token);

        if ($organization && !$user->getOrganizations(true)->contains($organization)) {
            throw new BadCredentialsException(
                sprintf("You don't have access to organization '%s'", $organization->getName())
            );
        }

        $authenticatedToken = $this->tokenFactory->create(
            $authenticatedToken->getUser(),
            $authenticatedToken->getCredentials(),
            $authenticatedToken->getProviderKey(),
            $organization,
            $authenticatedToken->getRoles()
        );

        return $authenticatedToken;
    }
}
