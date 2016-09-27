<?php

namespace Oro\Bundle\SecurityBundle\Authentication\Provider;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\Bundle\DoctrineBundle\Registry;

use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Provider\DaoAuthenticationProvider;
use Symfony\Component\Security\Core\User\UserInterface;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Security\LoginHistoryManager;
use Oro\Bundle\SecurityBundle\Authentication\Guesser\UserOrganizationGuesser;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationTokenFactoryInterface;

class UsernamePasswordOrganizationAuthenticationProvider extends DaoAuthenticationProvider
{
    /**
     * @var UsernamePasswordOrganizationTokenFactoryInterface
     */
    protected $tokenFactory;

    /**
     * @var UserInterface
     */
    protected $user;

    /**
     * @var  Registry
     */
    protected $doctrine;

    /**
     * @var LoginHistoryManager
     */
    protected $loginHistoryManager;

    /**
     * @param UsernamePasswordOrganizationTokenFactoryInterface $tokenFactory
     */
    public function setTokenFactory(UsernamePasswordOrganizationTokenFactoryInterface $tokenFactory)
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
                'Token Factory is not set in UsernamePasswordOrganizationAuthenticationProvider.'
            );
        }

        $guesser = new UserOrganizationGuesser();
        try {
            /**  @var TokenInterface $token */
            $authenticatedToken = parent::authenticate($token);
        } catch (BadCredentialsException $bce) {
            if ($this->user) {
                $this->loginHistoryManager->addLoginFailure($this->user, ClassUtils::getClass($this));
            }

            throw $bce;
        }

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

        $authenticatedToken = $this->tokenFactory->create(
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
    public function retrieveUser($username, UsernamePasswordToken $token)
    {
        $user = parent::retrieveUser($username, $token);

        if ($user) {
            $this->user = $user;
        }

        return $user;
    }

    /**
     * @param Registry $registry
     */
    public function setRegistry(Registry $registry)
    {
        $this->doctrine = $registry;
    }

    /**
     * @param LoginHistoryManager $loginHistoryManager
     */
    public function setLoginHistoryManager(LoginHistoryManager $loginHistoryManager)
    {
        $this->loginHistoryManager = $loginHistoryManager;
    }
}
