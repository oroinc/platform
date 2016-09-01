<?php

namespace Oro\Bundle\UserBundle\Security;

use Doctrine\ORM\EntityManager;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\GuardAuthenticatorInterface;

use Oro\Bundle\SecurityBundle\Authentication\Guesser\UserOrganizationGuesser;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationTokenFactoryInterface;

class ImpersonationAuthenticator implements GuardAuthenticatorInterface
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var UsernamePasswordOrganizationTokenFactoryInterface
     */
    protected $tokenFactory;

    /**
     * @param EntityManager $em
     * @param UsernamePasswordOrganizationTokenFactoryInterface $tokenFactory
     */
    public function __construct(EntityManager $em, UsernamePasswordOrganizationTokenFactoryInterface $tokenFactory)
    {
        $this->em = $em;
        $this->tokenFactory = $tokenFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getCredentials(Request $request)
    {
        return $request->query->get('_impersonation_token');
    }

    /**
     * {@inheritdoc}
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        // always returns a Impersonation object or throws an exception
        return $this->getImpersonation($credentials)->getUser();
    }

    /**
     * {@inheritdoc}
     */
    public function checkCredentials($credentials, UserInterface $user)
    {
        // checks are already done in getImpersonation
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        $message = strtr($exception->getMessageKey(), $exception->getMessageData());

        return new Response($message, 403);
    }

    /**
     * {@inheritdoc}
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsRememberMe()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function createAuthenticatedToken(UserInterface $user, $providerKey)
    {
        $guesser = new UserOrganizationGuesser();
        $organization = $guesser->guessByUser($user);

        if (!$organization) {
            throw new BadCredentialsException("You don't have active organization assigned.");
        }

        return $this->tokenFactory->create(
            $user,
            null,
            $providerKey,
            $organization,
            $user->getRoles()
        );
    }

    protected function getImpersonation($token)
    {
        $impersonation = $this->em
            ->getRepository('OroUserBundle:Impersonation')
            ->findOneBy(['token' => $token]);

        if (!$impersonation) {
            throw new AuthenticationCredentialsNotFoundException();
        }

        if ($impersonation->getLoginAt()) {
            throw new CustomUserMessageAuthenticationException('Token is already used.');
        }

        $now = new \DateTime();
        if ($impersonation->getExpireAt() <= $now) {
            throw new CustomUserMessageAuthenticationException('Token has expired.');
        }

        $impersonation->setLoginAt(new \DateTime());

        return $impersonation;
    }
}
