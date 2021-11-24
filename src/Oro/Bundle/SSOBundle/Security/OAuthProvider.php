<?php

namespace Oro\Bundle\SSOBundle\Security;

use HWI\Bundle\OAuthBundle\Security\Core\Authentication\Provider\OAuthProvider as HWIOAuthProvider;
use HWI\Bundle\OAuthBundle\Security\Core\Exception\OAuthAwareExceptionInterface;
use HWI\Bundle\OAuthBundle\Security\Core\User\OAuthAwareUserProviderInterface;
use HWI\Bundle\OAuthBundle\Security\Http\ResourceOwnerMap;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Guesser\OrganizationGuesserInterface;
use Oro\Bundle\SecurityBundle\Exception\BadUserOrganizationException;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;

/**
 * Sets organization to the token.
 */
class OAuthProvider extends HWIOAuthProvider
{
    /** @var ResourceOwnerMap */
    private $resourceOwnerMap;

    /** @var OAuthAwareUserProviderInterface */
    private $userProvider;

    /** @var UserCheckerInterface */
    private $userChecker;

    /** @var OAuthTokenFactoryInterface */
    private $tokenFactory;

    /** @var OrganizationGuesserInterface */
    private $organizationGuesser;

    public function __construct(
        OAuthAwareUserProviderInterface $userProvider,
        ResourceOwnerMap $resourceOwnerMap,
        UserCheckerInterface $userChecker,
        TokenStorageInterface $tokenStorage
    ) {
        parent::__construct($userProvider, $resourceOwnerMap, $userChecker, $tokenStorage);
        $this->userProvider = $userProvider;
        $this->resourceOwnerMap = $resourceOwnerMap;
        $this->userChecker = $userChecker;
    }

    public function setTokenFactory(OAuthTokenFactoryInterface $tokenFactory)
    {
        $this->tokenFactory = $tokenFactory;
    }

    public function setOrganizationGuesser(OrganizationGuesserInterface $organizationGuesser): void
    {
        $this->organizationGuesser = $organizationGuesser;
    }

    /**
     * {@inheritDoc}
     */
    public function authenticate(TokenInterface $token): ?TokenInterface
    {
        if (null === $this->tokenFactory) {
            throw new AuthenticationException('Token Factory is not set in OAuthProvider.');
        }
        if (null === $this->organizationGuesser) {
            throw new AuthenticationException('Organization Guesser is not set in OAuthProvider.');
        }

        /* @var OAuthToken $token */
        $resourceOwner = $this->resourceOwnerMap->getResourceOwnerByName($token->getResourceOwnerName());

        try {
            $userResponse = $resourceOwner->getUserInformation($token->getRawToken());
            $user = $this->userProvider->loadUserByOAuthUserResponse($userResponse);
        } catch (OAuthAwareExceptionInterface $e) {
            $e->setToken($token);
            $e->setResourceOwnerName($token->getResourceOwnerName());

            throw $e;
        }

        $organization = $this->guessOrganization($user, $token);

        $token = $this->tokenFactory->create($token->getRawToken(), $user->getUserRoles());
        $token->setResourceOwnerName($resourceOwner->getName());
        $token->setOrganization($organization);
        $token->setUser($user);
        $token->setAuthenticated(true);

        $this->userChecker->checkPostAuth($user);

        return $token;
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
