<?php

namespace Oro\Bundle\UserBundle\Security;

use HWI\Bundle\OAuthBundle\Security\Core\Authentication\Provider\OAuthProvider as HWIOAuthProvider;
use HWI\Bundle\OAuthBundle\Security\Core\Exception\OAuthAwareExceptionInterface;
use HWI\Bundle\OAuthBundle\Security\Core\User\OAuthAwareUserProviderInterface;
use HWI\Bundle\OAuthBundle\Security\Http\ResourceOwnerMap;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
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
     * @param OAuthAwareUserProviderInterface $userProvider User provider
     * @param ResourceOwnerMap $resourceOwnerMap Resource owner map
     * @param UserCheckerInterface $userChecker User checker
     */
    public function __construct(OAuthAwareUserProviderInterface $userProvider, ResourceOwnerMap $resourceOwnerMap, UserCheckerInterface $userChecker)
    {
        $this->userProvider = $userProvider;
        $this->resourceOwnerMap = $resourceOwnerMap;
        $this->userChecker = $userChecker;
    }

    /**
     * Attempts to authenticate a TokenInterface object.
     *
     * @param OAuthToken $token The TokenInterface instance to authenticate
     *
     * @return TokenInterface An authenticated TokenInterface instance, never null
     *
     * @throws AuthenticationException if the authentication fails
     */
    public function authenticate(TokenInterface $token)
    {
        $resourceOwner = $this->resourceOwnerMap->getResourceOwnerByName($token->getResourceOwnerName());

        try {
            $userResponse = $resourceOwner->getUserInformation($token->getRawToken());
            $user = $this->userProvider->loadUserByOAuthUserResponse($userResponse);
        } catch (OAuthAwareExceptionInterface $e) {
            $e->setToken($token);
            $e->setResourceOwnerName($token->getResourceOwnerName());

            throw $e;
        }

        $token = new OAuthToken($token->getRawToken(), $user->getRoles());
        $token->setResourceOwnerName($resourceOwner->getName());
        $token->setOrganizationContext($user->getOrganization());
        $token->setUser($user);
        $token->setAuthenticated(true);

        $this->userChecker->checkPostAuth($user);

        return $token;
    }
}
