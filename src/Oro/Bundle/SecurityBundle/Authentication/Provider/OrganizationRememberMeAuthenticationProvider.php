<?php

namespace Oro\Bundle\SecurityBundle\Authentication\Provider;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Guesser\OrganizationGuesserInterface;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationRememberMeTokenFactoryInterface;
use Oro\Bundle\SecurityBundle\Exception\BadUserOrganizationException;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Provider\RememberMeAuthenticationProvider;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * The authentication provider to retrieve the user and the organization for a OrganizationRememberMeToken.
 */
class OrganizationRememberMeAuthenticationProvider extends RememberMeAuthenticationProvider
{
    /** @var OrganizationRememberMeTokenFactoryInterface */
    private $tokenFactory;

    /** @var OrganizationGuesserInterface */
    private $organizationGuesser;

    public function setTokenFactory(OrganizationRememberMeTokenFactoryInterface $tokenFactory)
    {
        $this->tokenFactory = $tokenFactory;
    }

    public function setOrganizationGuesser(OrganizationGuesserInterface $organizationGuesser)
    {
        $this->organizationGuesser = $organizationGuesser;
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
        if (null === $this->organizationGuesser) {
            throw new AuthenticationException(
                'Organization Guesser is not set in OrganizationRememberMeAuthenticationProvider.'
            );
        }

        /**  @var TokenInterface $token */
        $authenticatedToken = parent::authenticate($token);

        /** @var User $user */
        $user = $authenticatedToken->getUser();
        $organization = $this->guessOrganization($user, $token);

        return $this->tokenFactory->create(
            $user,
            $authenticatedToken->getProviderKey(),
            $authenticatedToken->getSecret(),
            $organization
        );
    }

    private function guessOrganization(AbstractUser $user, TokenInterface $token): Organization
    {
        $organization = $this->organizationGuesser->guess($user, $token);
        if (null === $organization) {
            throw new BadUserOrganizationException('The user does not have active organization assigned to it.');
        }
        if (!$user->isBelongToOrganization($organization, true)) {
            throw new BadUserOrganizationException(sprintf(
                'The user does not have access to organization "%s".',
                $organization->getName()
            ));
        }

        return $organization;
    }
}
