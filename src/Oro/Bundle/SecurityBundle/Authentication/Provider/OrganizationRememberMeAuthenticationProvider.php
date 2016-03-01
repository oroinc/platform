<?php

namespace Oro\Bundle\SecurityBundle\Authentication\Provider;

use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Provider\RememberMeAuthenticationProvider;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\SecurityBundle\Authentication\Guesser\UserOrganizationGuesser;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationRememberMeTokenFactoryInterface;

class OrganizationRememberMeAuthenticationProvider extends RememberMeAuthenticationProvider
{
    /**
     * @var OrganizationRememberMeTokenFactoryInterface
     */
    protected $tokenFactory;

    /**
     * @param OrganizationRememberMeTokenFactoryInterface $tokenFactory
     */
    public function setTokenFactory(OrganizationRememberMeTokenFactoryInterface $tokenFactory)
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
                'Token Factory is not set in OrganizationRememberMeAuthenticationProvider.'
            );
        }

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

        $authenticatedToken = $this->tokenFactory
            ->create(
                $user,
                $authenticatedToken->getProviderKey(),
                $authenticatedToken->getKey(),
                $organization
            );

        return $authenticatedToken;
    }
}
