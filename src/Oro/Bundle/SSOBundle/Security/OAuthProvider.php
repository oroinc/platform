<?php

namespace Oro\Bundle\SSOBundle\Security;

use HWI\Bundle\OAuthBundle\Security\Core\Authentication\Token\OAuthToken;
use HWI\Bundle\OAuthBundle\Security\Core\Exception\OAuthAwareExceptionInterface;
use HWI\Bundle\OAuthBundle\Security\Core\User\OAuthAwareUserProviderInterface;
use HWI\Bundle\OAuthBundle\Security\Http\ResourceOwnerMapInterface;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Guesser\OrganizationGuesserInterface;
use Oro\Bundle\SecurityBundle\Exception\BadUserOrganizationException;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;

/**
 * Sets organization to the token.
 */
class OAuthProvider implements AuthenticationProviderInterface
{
    private ResourceOwnerMapInterface $resourceOwnerMap;
    private OAuthAwareUserProviderInterface $userProvider;
    private UserCheckerInterface $userChecker;
    private OAuthTokenFactoryInterface $tokenFactory;
    private OrganizationGuesserInterface $organizationGuesser;

    public function __construct(
        OAuthAwareUserProviderInterface $userProvider,
        ResourceOwnerMapInterface $resourceOwnerMap,
        UserCheckerInterface $userChecker
    ) {
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

    public function supports(TokenInterface $token): bool
    {
        if (!$token instanceof OAuthToken) {
            return false;
        }

        return $this->resourceOwnerMap->hasResourceOwnerByName($token->getResourceOwnerName());
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate(TokenInterface $token): ?TokenInterface
    {
        if (!isset($this->tokenFactory)) {
            throw new AuthenticationException('Token Factory is not set in OAuthProvider.');
        }
        if (!isset($this->organizationGuesser)) {
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
