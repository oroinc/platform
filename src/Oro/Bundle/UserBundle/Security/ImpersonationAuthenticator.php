<?php

namespace Oro\Bundle\UserBundle\Security;

use Doctrine\ORM\EntityManager;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
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
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Event\ImpersonationSuccessEvent;

class ImpersonationAuthenticator implements GuardAuthenticatorInterface
{
    const TOKEN_PARAMETER = '_impersonation_token';
    const NOTIFY_PARAMETER = '_impersonation_notify';

    /** @var EntityManager */
    protected $em;

    /** @var UsernamePasswordOrganizationTokenFactoryInterface */
    protected $tokenFactory;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /**
     * @param EntityManager $em
     * @param UsernamePasswordOrganizationTokenFactoryInterface $tokenFactory
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        EntityManager $em,
        UsernamePasswordOrganizationTokenFactoryInterface $tokenFactory,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->em = $em;
        $this->tokenFactory = $tokenFactory;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function getCredentials(Request $request)
    {
        return $request->query->get(static::TOKEN_PARAMETER);
    }

    /**
     * {@inheritdoc}
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        // always returns an Impersonation object or throws an exception
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
        if ($request->query->get(static::NOTIFY_PARAMETER)) {
            $event = new ImpersonationSuccessEvent($token->getUser());
            $this->eventDispatcher->dispatch(ImpersonationSuccessEvent::EVENT_NAME, $event);
        }
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
        /** @var User $user */
        $organization = $guesser->guessByUser($user);

        if (!$organization) {
            throw new BadCredentialsException("You don't have active organization assigned.");
        }

        return $this->tokenFactory->create($user, null, $providerKey, $organization, $user->getRoles());
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

        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        if ($impersonation->getExpireAt() <= $now) {
            throw new CustomUserMessageAuthenticationException('Token has expired.');
        }

        $impersonation->setLoginAt($now);

        return $impersonation;
    }
}
