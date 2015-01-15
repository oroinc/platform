<?php

namespace Oro\Bundle\UserBundle\Security;

use HWI\Bundle\OAuthBundle\Security\Core\Authentication\Provider\OAuthProvider as HWIOAuthProvider;
use HWI\Bundle\OAuthBundle\Security\Core\Exception\OAuthAwareExceptionInterface;
use HWI\Bundle\OAuthBundle\Security\Core\User\OAuthAwareUserProviderInterface;
use HWI\Bundle\OAuthBundle\Security\Http\ResourceOwnerMap;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserCheckerInterface;

/**
 * @author Miloslav Nenadal <miloslav.nenadal@imatic.cz>
 */
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

    public function authenticate(TokenInterface $token)
    {
        /* @var OAuthToken $token */
        $resourceOwner = $this->resourceOwnerMap->getResourceOwnerByName($token->getResourceOwnerName());

        $userResponse = $resourceOwner->getUserInformation($token->getRawToken());

        try {
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
