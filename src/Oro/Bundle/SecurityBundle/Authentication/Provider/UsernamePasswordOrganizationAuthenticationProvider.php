<?php

namespace Oro\Bundle\SecurityBundle\Authentication\Provider;


use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Symfony\Component\Security\Core\Authentication\Provider\DaoAuthenticationProvider;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UsernamePasswordOrganizationAuthenticationProvider extends DaoAuthenticationProvider
{
    protected $providerKey;

    /**
     * Constructor.
     *
     * @param UserProviderInterface   $userProvider               An UserProviderInterface instance
     * @param UserCheckerInterface    $userChecker                An UserCheckerInterface instance
     * @param string                  $providerKey                The provider key
     * @param EncoderFactoryInterface $encoderFactory             An EncoderFactoryInterface instance
     * @param Boolean                 $hideUserNotFoundExceptions Whether to hide user not found exception or not
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
        $usernamePasswordToken = parent::authenticate($token);

        $this->checkUserOrganization($usernamePasswordToken->getUser(), $token->getOrganizationContext());

        $authenticatedToken = new UsernamePasswordOrganizationToken(
            $usernamePasswordToken->getUser(),
            $usernamePasswordToken->getCredentials(),
            $usernamePasswordToken->getProviderKey(),
            $usernamePasswordToken->getRoles(),
            $token->getOrganizationContext()
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

    protected function checkUserOrganization(UserInterface $user, Organization $organization)
    {
        /**
         * todo: Add check for organization
         */
        return true;
    }
}
