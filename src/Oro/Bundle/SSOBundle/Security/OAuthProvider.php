<?php

namespace Oro\Bundle\SSOBundle\Security;

use HWI\Bundle\OAuthBundle\Security\Core\Authentication\Provider\OAuthProvider as HWIOAuthProvider;
use HWI\Bundle\OAuthBundle\Security\Core\Exception\OAuthAwareExceptionInterface;
use HWI\Bundle\OAuthBundle\Security\Core\User\OAuthAwareUserProviderInterface;
use HWI\Bundle\OAuthBundle\Security\Http\ResourceOwnerMap;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Guesser\UserOrganizationGuesser;
use Oro\Bundle\UserBundle\Entity\User;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;

class OAuthProvider extends HWIOAuthProvider
{
    /**
     * @var ResourceOwnerMap
     */
    protected $resourceOwnerMap;

    /**
     * @var OAuthAwareUserProviderInterface
     */
    protected $userProvider;

    /**
     * @var UserCheckerInterface
     */
    protected $userChecker;

    /**
     * @var OAuthTokenFactoryInterface
     */
    protected $tokenFactory;

    /**
     * @param OAuthAwareUserProviderInterface $userProvider User provider
     * @param ResourceOwnerMap $resourceOwnerMap Resource owner map
     * @param UserCheckerInterface $userChecker User checker
     */
    public function __construct(
        OAuthAwareUserProviderInterface $userProvider,
        ResourceOwnerMap $resourceOwnerMap,
        UserCheckerInterface $userChecker
    ) {
        parent::__construct($userProvider, $resourceOwnerMap, $userChecker);
        $this->userProvider = $userProvider;
        $this->resourceOwnerMap = $resourceOwnerMap;
        $this->userChecker = $userChecker;
    }

    /**
     * @param OAuthTokenFactoryInterface $tokenFactory
     */
    public function setTokenFactory(OAuthTokenFactoryInterface $tokenFactory)
    {
        $this->tokenFactory = $tokenFactory;
    }

    /**
     * {@inheritDoc}
     *
     * @throws OAuthAwareExceptionInterface
     */
    public function authenticate(TokenInterface $token)
    {
        if (null === $this->tokenFactory) {
            throw new AuthenticationException('Token Factory is not set in OAuthProvider.');
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

        $token = $this->tokenFactory->create($token->getRawToken(), $user->getRoles());
        $token->setResourceOwnerName($resourceOwner->getName());
        $token->setOrganizationContext($organization);
        $token->setUser($user);
        $token->setAuthenticated(true);

        $this->userChecker->checkPostAuth($user);

        return $token;
    }

    /**
     * Guess organization
     *
     * @param User $user
     * @param TokenInterface $token
     *
     * @return Organization
     *
     * @throws BadCredentialsException
     */
    protected function guessOrganization(User $user, TokenInterface $token)
    {
        $organizationGuesser = new UserOrganizationGuesser();
        $organization = $organizationGuesser->guess($user, $token);
        if (!$organization) {
            throw new BadCredentialsException("You don't have active organization assigned.");
        } elseif (!$user->getOrganizations(true)->contains($organization)) {
            throw new BadCredentialsException(
                sprintf("You don't have access to organization '%s'", $organization->getName())
            );
        }

        return $organization;
    }
}
