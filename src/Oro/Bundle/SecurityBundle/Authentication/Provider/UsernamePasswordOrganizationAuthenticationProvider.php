<?php

namespace Oro\Bundle\SecurityBundle\Authentication\Provider;

use Symfony\Component\Security\Core\Authentication\Provider\DaoAuthenticationProvider;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\UserBundle\Entity\User;

class UsernamePasswordOrganizationAuthenticationProvider extends DaoAuthenticationProvider
{
    /**
     * @var string
     */
    protected $providerKey;

    /**
     * @param UserProviderInterface   $userProvider
     * @param UserCheckerInterface    $userChecker
     * @param string                  $providerKey
     * @param EncoderFactoryInterface $encoderFactory
     * @param bool                    $hideUserNotFoundExceptions
     */
    public function __construct(
        UserProviderInterface $userProvider,
        UserCheckerInterface $userChecker,
        $providerKey,
        EncoderFactoryInterface $encoderFactory,
        $hideUserNotFoundExceptions = true
    ) {
        parent::__construct($userProvider, $userChecker, $providerKey, $encoderFactory, $hideUserNotFoundExceptions);

        $this->providerKey = $providerKey;
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate(TokenInterface $token)
    {
        /**  @var TokenInterface $token */
        $authenticatedToken = parent::authenticate($token);

        /** @var User $user */
        $user         = $authenticatedToken->getUser();
        $organization = $this->getOrganization($user, $token);

        if (!($organization && $user->getOrganizations()->contains($organization))) {
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

    /**
     * {@inheritdoc}
     */
    public function supports(TokenInterface $token)
    {
        $knownToken = $token instanceof UsernamePasswordOrganizationToken || $token instanceof UsernamePasswordToken;

        return $knownToken && $this->providerKey === $token->getProviderKey();
    }

    /**
     * @param TokenInterface $token
     *
     * @return Organization|null
     */
    protected function getOrganization(User $user, TokenInterface $token)
    {
        if ($token instanceof UsernamePasswordOrganizationToken) {
            return $token->getOrganizationContext();
        }

        return $this->guessPreferredOrganization($user);
    }

    protected function guessPreferredOrganization(User $user)
    {

    }
}
