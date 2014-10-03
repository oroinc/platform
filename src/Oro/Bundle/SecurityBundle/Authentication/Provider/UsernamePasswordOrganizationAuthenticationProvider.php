<?php

namespace Oro\Bundle\SecurityBundle\Authentication\Provider;

use Symfony\Component\Security\Core\Authentication\Provider\DaoAuthenticationProvider;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
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
        /**  @var UsernamePasswordOrganizationToken $token */
        $usernamePasswordToken = parent::authenticate($token);

        $this->checkUserOrganization($usernamePasswordToken->getUser(), $token->getOrganizationContext());

        $authenticatedToken = new UsernamePasswordOrganizationToken(
            $usernamePasswordToken->getUser(),
            $usernamePasswordToken->getCredentials(),
            $usernamePasswordToken->getProviderKey(),
            $token->getOrganizationContext(),
            $usernamePasswordToken->getRoles()
        );

        return $authenticatedToken;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(TokenInterface $token)
    {
        return $token instanceof UsernamePasswordOrganizationToken && $this->providerKey === $token->getProviderKey();
    }

    /**
     * @param User         $user
     * @param Organization $organization
     * @throws BadCredentialsException
     */
    protected function checkUserOrganization(User $user, Organization $organization)
    {
        if (!$user->getOrganizations()->contains($organization) || !$organization->isEnabled()) {
            throw new BadCredentialsException(
                sprintf("You don't have access to organization '%s'", $organization->getName())
            );
        }
    }
}
